#!/usr/bin/env python3
"""Fast structural and semantic checks for vieclam88 Claude context and plan baseline."""
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


def read(rel: str) -> str:
    path = ROOT / rel
    return path.read_text(encoding="utf-8") if path.exists() else ""


required = [
    "CLAUDE.md",
    ".claude/settings.json",
    "docs/INDEX.md",
    "docs/CLAUDE-SKILLS.md",
    "docs/CONTEXT-MAP.md",
    "docs/PROJECT-STATUS.md",
    "docs/PHASE-1-SCOPE.md",
    "docs/CORE-FLOWS.md",
    "docs/DATABASE-DICTIONARY.md",
    "docs/ERD.md",
    "docs/ROUTE-MAP.md",
    "docs/ACCEPTANCE-CRITERIA.md",
    "docs/decisions/INDEX.md",
]
for rel in required:
    if not (ROOT / rel).exists():
        error(f"Missing required file: {rel}")

claude = read("CLAUDE.md")
claude_lines = claude.count("\n") + 1
if claude_lines > 80:
    error(f"CLAUDE.md is {claude_lines} lines; optimized global context must stay <= 80")
if "Trạng thái phiên gần nhất" in claude or "Bước tiếp theo (session sau)" in claude:
    error("CLAUDE.md contains session status; keep session state only in docs/PROJECT-STATUS.md")

retired_rules = {
    "data-model.md",
    "roles-business-rules.md",
    "scope-standards.md",
    "security-seo-testing.md",
    "tech-stack.md",
}
rules_dir = ROOT / ".claude/rules"
for retired in retired_rules:
    if (rules_dir / retired).exists():
        error(f"Retired broad rule still exists: .claude/rules/{retired}")

expected_rules = {
    "architecture.md", "database-schema.md", "job-domain.md", "application-domain.md",
    "authorization.md", "hr-admin.md", "public-site.md", "security.md", "testing.md",
    "seo-public.md", "ui-guidelines.md", "docs-governance.md",
}
actual_rules = {p.name for p in rules_dir.glob("*.md")}
for missing in sorted(expected_rules - actual_rules):
    error(f"Missing optimized rule: .claude/rules/{missing}")
for path in sorted(rules_dir.glob("*.md")):
    text = path.read_text(encoding="utf-8")
    if not text.startswith("---\n") or "\npaths:\n" not in text.split("---", 2)[1]:
        error(f"Rule is not path-scoped: {path.relative_to(ROOT)}")
    if text.count("\n") + 1 > 45:
        warning(f"Rule is over 45 lines and may waste context: {path.relative_to(ROOT)}")

expected_skills = [
    "vibe-task", "plan-next", "implement", "db-task", "test-task",
    "verify-task", "review-changes", "fix-review", "release-gate", "handoff",
]
for skill in expected_skills:
    p = ROOT / f".claude/skills/{skill}/SKILL.md"
    if not p.exists():
        error(f"Missing skill: {p.relative_to(ROOT)}")

for rel in [
    ".claude/skills/vibe-task/task-router.md",
    ".claude/skills/vibe-task/quality-gates.md",
    ".claude/skills/vibe-task/report-template.md",
    ".claude/skills/vibe-task/examples.md",
    ".claude/skills/review-changes/review-rubric.md",
    "docs/CLAUDE-SKILLS.md",
    "scripts/check-claude-skills.py",
]:
    if not (ROOT / rel).exists():
        error(f"Missing smart-skill resource: {rel}")

settings_path = ROOT / ".claude/settings.json"
if settings_path.exists():
    try:
        data = json.loads(settings_path.read_text(encoding="utf-8"))
    except json.JSONDecodeError as exc:
        error(f"Invalid .claude/settings.json: {exc}")
    else:
        deny = data.get("permissions", {}).get("deny", [])
        if "Read(./.env.*)" in deny:
            error("Do not block .env.example with Read(./.env.*)")

if any(ROOT.rglob("__pycache__")) or any(ROOT.rglob("*.pyc")):
    error("Python cache artifact exists; remove __pycache__/ and *.pyc")

status = read("docs/PROJECT-STATUS.md")
if status.count("\n") + 1 > 40:
    warning("docs/PROJECT-STATUS.md exceeds 40 lines")

# ADR split: exactly ADR-001..078 once in topic files and listed in index.
decision_files = sorted((ROOT / "docs/decisions").glob("*.md"))
adr_occurrences: dict[int, list[str]] = {}
for p in decision_files:
    if p.name == "INDEX.md":
        continue
    for raw in re.findall(r"^## ADR-(\d{3}) —", p.read_text(encoding="utf-8"), re.M):
        adr_occurrences.setdefault(int(raw), []).append(p.name)
for adr in range(1, 79):
    files = adr_occurrences.get(adr, [])
    if len(files) != 1:
        error(f"ADR-{adr:03d} must exist exactly once in topic files; found {files}")
decision_index = read("docs/decisions/INDEX.md")
for adr in range(1, 79):
    if f"ADR-{adr:03d}" not in decision_index:
        error(f"Decision index missing ADR-{adr:03d}")
if "Không thêm ADR mới vào file này" not in read("docs/DECISIONS.md"):
    warning("docs/DECISIONS.md should remain a compatibility pointer")

# No stale references to retired context files.
retired_refs = [
    ".claude/rules/data-model.md", ".claude/rules/roles-business-rules.md",
    ".claude/rules/scope-standards.md", ".claude/rules/security-seo-testing.md",
    ".claude/rules/tech-stack.md",
]
for p in ROOT.rglob("*.md"):
    if ".git" in p.parts:
        continue
    text = p.read_text(encoding="utf-8")
    for ref in retired_refs:
        if ref in text:
            error(f"Stale rule reference `{ref}` in {p.relative_to(ROOT)}")

# Validate key local markdown links and backticked project paths used in indexes/context/rules/skills.
files_to_check = [ROOT / "CLAUDE.md", ROOT / "README.md", ROOT / "docs/INDEX.md", ROOT / "docs/CONTEXT-MAP.md"]
files_to_check += list(rules_dir.glob("*.md")) + list((ROOT / ".claude/skills").glob("**/*.md"))
for p in files_to_check:
    text = p.read_text(encoding="utf-8")
    candidates = set(re.findall(r"`((?:docs|\.claude)/[^`#]+(?:\.md|/))`", text))
    for rel in candidates:
        rel = rel.rstrip("/")
        if not (ROOT / rel).exists():
            error(f"Broken project path `{rel}` referenced by {p.relative_to(ROOT)}")

core_flows = read("docs/CORE-FLOWS.md")
dictionary = read("docs/DATABASE-DICTIONARY.md")
erd = read("docs/ERD.md")
route_map = read("docs/ROUTE-MAP.md")
acceptance = read("docs/ACCEPTANCE-CRITERIA.md")
phase_scope = read("docs/PHASE-1-SCOPE.md")
roadmap = read("ROADMAP.md")
job_rule = read(".claude/rules/job-domain.md")
application_rule = read(".claude/rules/application-domain.md")
auth_rule = read(".claude/rules/authorization.md")

# Core semantic baseline checks.
if re.search(r"\|\s*job_description\s*\|\s*text\s*\|\s*—\s*\|\s*không\s*\|", dictionary):
    error("jobs.job_description is documented NOT NULL, contradicting Job Draft Contract")
if re.search(r"tạo verification\s*→\s*cập nhật `jobs\.last_verified_at`", dictionary):
    error("Dictionary says every verification updates last_verified_at")
if re.search(r"có ít nhất một .*still_open.*trong lịch sử", core_flows + dictionary + job_rule, re.I):
    error("Stale any-still_open-in-history publish logic remains")
if "latest" not in job_rule.lower() and "mới nhất" not in job_rule.lower():
    error("Job rule does not require latest verification")
if "Query **tất cả** Candidate" not in core_flows or "multiple_exact_matches" not in core_flows:
    error("Core Flows missing all-phone-root/multiple-exact matching contract")
if "toàn merged family" not in core_flows:
    error("Core Flows missing merged-family same-job check")
if "named lock" not in application_rule.lower():
    error("Application rule missing named-lock concurrency invariant")
if "EnsureUserIsActive" not in auth_rule or "EnsureUserIsActive" not in route_map:
    error("Active-user middleware missing from authorization rule or Route Map")

# Business table parity.
dict_tables = set(re.findall(r"^## 9\.\d+\.\s*`(\w+)`", dictionary, re.M))
erd_tables = set(re.findall(r"^\s{4}(\w+)\s*\{", erd, re.M))
if dict_tables != erd_tables:
    error(f"Dictionary/ERD table mismatch: only_dictionary={sorted(dict_tables-erd_tables)}, only_erd={sorted(erd_tables-dict_tables)}")
if len(dict_tables) != 28:
    warning(f"Expected 28 Phase 1 business tables, found {len(dict_tables)}")
for phase2_table in ["lead_requests", "favorites", "application_assignment_histories"]:
    if phase2_table in dict_tables or phase2_table in erd_tables:
        error(f"Phase 2 table `{phase2_table}` appears in Phase 1 schema")

# Required route names and route row clarity.
for route_name in [
    "hr.password.change", "hr.password.update", "hr.staff.reset-password", "hr.staff.lock",
    "hr.staff.unlock", "hr.duplicate-reviews.index", "hr.duplicate-reviews.show",
    "hr.duplicate-reviews.resolve", "hr.candidates.anonymize", "hr.branches.restore",
]:
    if route_name not in route_map:
        error(f"Route Map missing `{route_name}`")
if re.search(r"\|\s*(?:GET/POST|PUT/DELETE|GET,POST|PUT,DELETE)\s*\|", route_map):
    error("Route Map groups multiple HTTP methods in one row")

# Contract markers.
for needle, where in [
    ("NOT NULL, UNIQUE", dictionary),
    ("UNIQUE(candidate_id, job_id)", dictionary),
    ("candidate_duplicate_reviews", dictionary),
    ("22 điều kiện", phase_scope),
    ("draft`/`paused", roadmap + dictionary),
    ("Negotiable mode", core_flows),
    ("Numeric/described mode", core_flows),
]:
    if needle not in where:
        error(f"Missing baseline marker: {needle}")

# Banned vague wording in live contracts.
for name, text in [
    ("docs/ROUTE-MAP.md", route_map),
    ("docs/CORE-FLOWS.md", core_flows),
    ("docs/DATABASE-DICTIONARY.md", dictionary),
]:
    for phrase in ["resource route tương tự", "route tương tự", "validate phù hợp", "xử lý tương tự"]:
        if phrase in text:
            error(f"{name} contains vague phrase `{phrase}`")

for message in WARNINGS:
    print(f"WARN: {message}")
for message in ERRORS:
    print(f"ERROR: {message}")
if ERRORS:
    print(f"FAILED: {len(ERRORS)} error(s), {len(WARNINGS)} warning(s)")
    sys.exit(1)
print(f"OK: Claude context and plan baseline passed with {len(WARNINGS)} warning(s)")
