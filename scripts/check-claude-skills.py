#!/usr/bin/env python3
"""Validate project-local Claude Code skills without external dependencies."""
from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SKILLS = ROOT / ".claude" / "skills"
ERRORS: list[str] = []
WARNINGS: list[str] = []

EXPECTED = {
    "vibe-task",
    "plan-next",
    "implement",
    "db-task",
    "test-task",
    "verify-task",
    "review-changes",
    "fix-review",
    "release-gate",
    "handoff",
}
MANUAL_SIDE_EFFECT = {
    "vibe-task",
    "implement",
    "db-task",
    "test-task",
    "fix-review",
    "handoff",
}
VALID_EFFORT = {"low", "medium", "high", "xhigh", "max"}


def error(message: str) -> None:
    ERRORS.append(message)


def warning(message: str) -> None:
    WARNINGS.append(message)


def parse_frontmatter(text: str, path: Path) -> tuple[dict[str, str], str]:
    if not text.startswith("---\n"):
        error(f"Missing YAML frontmatter: {path.relative_to(ROOT)}")
        return {}, text
    parts = text.split("---", 2)
    if len(parts) < 3:
        error(f"Unclosed YAML frontmatter: {path.relative_to(ROOT)}")
        return {}, text
    raw, body = parts[1], parts[2].lstrip("\n")
    data: dict[str, str] = {}
    for line in raw.splitlines():
        if not line.strip() or line.lstrip().startswith("#"):
            continue
        match = re.match(r"^([a-z][a-z0-9-]*):\s*(.*?)\s*$", line)
        if not match:
            error(f"Unsupported frontmatter line `{line}` in {path.relative_to(ROOT)}")
            continue
        key, value = match.groups()
        value = value.strip().strip('"').strip("'")
        data[key] = value
    return data, body


if not SKILLS.exists():
    error("Missing .claude/skills directory")
else:
    actual = {p.name for p in SKILLS.iterdir() if p.is_dir()}
    for name in sorted(EXPECTED - actual):
        error(f"Missing expected skill: {name}")
    for name in sorted(actual):
        if not re.fullmatch(r"[a-z0-9]+(?:-[a-z0-9]+)*", name):
            error(f"Skill directory must be kebab-case: {name}")
        skill_file = SKILLS / name / "SKILL.md"
        if not skill_file.exists():
            error(f"Missing SKILL.md for skill: {name}")
            continue
        text = skill_file.read_text(encoding="utf-8")
        meta, body = parse_frontmatter(text, skill_file)
        if not meta.get("description"):
            error(f"Skill `{name}` must have a description")
        if len(meta.get("description", "")) > 1536:
            error(f"Skill `{name}` description exceeds 1536 characters")
        if text.count("\n") + 1 > 500:
            error(f"Skill `{name}` exceeds 500 lines")
        if meta.get("effort") and meta["effort"] not in VALID_EFFORT:
            error(f"Skill `{name}` has invalid effort `{meta['effort']}`")
        if meta.get("context") == "fork" and not meta.get("agent"):
            warning(f"Forked skill `{name}` has no explicit agent")
        if meta.get("argument-hint") and "$ARGUMENTS" not in body:
            error(f"Skill `{name}` has argument-hint but does not use $ARGUMENTS")
        if name in MANUAL_SIDE_EFFECT and meta.get("disable-model-invocation") != "true":
            error(f"Side-effect skill `{name}` must set disable-model-invocation: true")
        if "commit/push" not in body and name in MANUAL_SIDE_EFFECT:
            warning(f"Skill `{name}` does not explicitly forbid commit/push")

        # Validate relative markdown links inside the skill folder.
        for target in re.findall(r"\[[^\]]+\]\(([^)]+\.md)\)", text):
            if target.startswith(("http://", "https://", "/")):
                continue
            resolved = (skill_file.parent / target).resolve()
            if not resolved.exists() or ROOT not in resolved.parents:
                error(f"Broken skill link `{target}` in {skill_file.relative_to(ROOT)}")

    commands = ROOT / ".claude" / "commands"
    if commands.exists():
        command_names = {p.stem for p in commands.glob("*.md")}
        conflicts = command_names & actual
        for name in sorted(conflicts):
            warning(f"Command/skill name conflict; skill takes precedence: {name}")

required_support = {
    "vibe-task/task-router.md",
    "vibe-task/quality-gates.md",
    "vibe-task/report-template.md",
    "vibe-task/examples.md",
    "review-changes/review-rubric.md",
}
for rel in sorted(required_support):
    if not (SKILLS / rel).exists():
        error(f"Missing skill supporting file: .claude/skills/{rel}")

for message in WARNINGS:
    print(f"WARN: {message}")
for message in ERRORS:
    print(f"ERROR: {message}")
if ERRORS:
    print(f"FAILED: {len(ERRORS)} error(s), {len(WARNINGS)} warning(s)")
    sys.exit(1)
print(f"OK: {len(EXPECTED)} Claude skills passed with {len(WARNINGS)} warning(s)")
