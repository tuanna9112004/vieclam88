#!/usr/bin/env python3
"""Fast consistency checks for Claude Code project configuration."""
from __future__ import annotations

import json
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ERRORS: list[str] = []
WARNINGS: list[str] = []


def error(message: str) -> None:
    ERRORS.append(message)


def warning(message: str) -> None:
    WARNINGS.append(message)


required = [
    "CLAUDE.md",
    ".claude/settings.json",
    "docs/CONTEXT-MAP.md",
    "docs/PROJECT-STATUS.md",
    "docs/DATABASE-DICTIONARY.md",
    "docs/ERD.md",
    "docs/ROUTE-MAP.md",
    "docs/ACCEPTANCE-CRITERIA.md",
]
for rel in required:
    if not (ROOT / rel).exists():
        error(f"Missing required file: {rel}")

claude = ROOT / "CLAUDE.md"
if claude.exists():
    text = claude.read_text(encoding="utf-8")
    lines = text.count("\n") + 1
    if lines > 120:
        error(f"CLAUDE.md is {lines} lines; keep it <= 120")
    if re.search(r"(?<!`)@(?:\.?/)?[\w./-]+", text):
        warning("CLAUDE.md may contain @ imports; imports increase startup context")

rules = ROOT / ".claude/rules"
for path in sorted(rules.glob("*.md")):
    text = path.read_text(encoding="utf-8")
    if not text.startswith("---\n") or "\npaths:\n" not in text.split("---", 2)[1]:
        error(f"Rule is not path-scoped: {path.relative_to(ROOT)}")

settings = ROOT / ".claude/settings.json"
if settings.exists():
    try:
        data = json.loads(settings.read_text(encoding="utf-8"))
    except json.JSONDecodeError as exc:
        error(f"Invalid .claude/settings.json: {exc}")
    else:
        deny = data.get("permissions", {}).get("deny", [])
        if "Read(./.env.*)" in deny:
            error("Do not block .env.example with Read(./.env.*)")
        if "Read(./storage/**)" in deny:
            warning("Blocking all storage prevents useful debugging")

if (ROOT / "viec3mien_giaodienweb").exists():
    error("Duplicate legacy image directory still exists: viec3mien_giaodienweb/")

dictionary = ROOT / "docs/DATABASE-DICTIONARY.md"
if dictionary.exists() and "[đề xuất]" in dictionary.read_text(encoding="utf-8"):
    warning("Database dictionary still contains [đề xuất]; do not generate final migrations yet")

status = ROOT / "docs/PROJECT-STATUS.md"
if status.exists() and status.read_text(encoding="utf-8").count("\n") + 1 > 45:
    warning("PROJECT-STATUS.md is over 45 lines; trim old state")

for message in WARNINGS:
    print(f"WARN: {message}")
for message in ERRORS:
    print(f"ERROR: {message}")

if ERRORS:
    print(f"FAILED: {len(ERRORS)} error(s), {len(WARNINGS)} warning(s)")
    sys.exit(1)
print(f"OK: Claude configuration passed with {len(WARNINGS)} warning(s)")
