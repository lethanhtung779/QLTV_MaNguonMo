#!/usr/bin/env python3
"""Paper Haven safe local credential audit. No network, DB or secret output.

Usage: python tools/paper_haven_safety_audit.py [--apply] [--root PATH]
Default scans without modification. --apply repairs only two recognizable
hardcoded Admin QA password contexts after backing up touched files off-repo.
"""
from __future__ import annotations

import argparse
import datetime as dt
import os
from pathlib import Path
import re
import shutil
import sys

SCAN_EXT = {'.php', '.py', '.js', '.cjs', '.mjs', '.json', '.md', '.txt', '.ps1'}
SKIP_DIRS = {'.git', 'vendor', 'node_modules', '__pycache__', '.venv', 'dist', 'book-covers', 'images'}
ADMIN_EMAIL = 'admin@paperpage.local'
ENV_KEY = 'PAPER_HAVEN_QA_ADMIN_PASSWORD'
# Literal password only when placed directly in the login form or adjacent Admin post data.
JS_FILL = re.compile(r'(?P<before>\.fill\(\s*[\'\"]\[name=password\][\'\"]\s*,\s*)(?P<q>[\'\"])(?P<secret>[^\'\"\r\n]{24,})(?P=q)(?P<after>\s*\))')
PY_LOGIN = re.compile(r'(?P<before>[\'\"]password[\'\"]\s*:\s*)(?P<q>[\'\"])(?P<secret>[^\'\"\r\n]{24,})(?P=q)')
PY_ADMIN_PASS = re.compile(r'(?P<before>\badmin_pass\s*=\s*)(?P<q>[\'\"])(?P<secret>[^\'\"\r\n]{24,})(?P=q)')
# Known-risk paths contain QA auth material; report filename only.
SENSITIVE_PATHS = ('storage/http_qa.json', 'storage/backups/', '.env')


def safe_files(root: Path):
    for parent, dirs, names in os.walk(root):
        dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
        for name in names:
            p = Path(parent) / name
            rel = p.relative_to(root).as_posix()
            if rel.startswith('public/assets/') and p.suffix.lower() not in {'.js', '.css'}:
                continue
            if p.suffix.lower() not in SCAN_EXT:
                continue
            if p.stat().st_size > 2_000_000:
                continue
            yield p, rel


def matches(text: str, rel: str):
    """Only auto-repair Admin plaintext with local surrounding Admin identifier."""
    language = Path(rel).suffix.lower()
    patterns = [(JS_FILL, 'JavaScript', lambda m: m.group('before') + 'process.env.' + ENV_KEY + m.group('after'))] if language in {'.js', '.cjs', '.mjs'} else []
    if language == '.py':
        patterns += [(PY_LOGIN, 'Python', lambda m: m.group('before') + 'os.environ[\'' + ENV_KEY + '\']'),
                     (PY_ADMIN_PASS, 'Python', lambda m: m.group('before') + 'os.environ[\'' + ENV_KEY + '\']')]
    found = []
    for pat, lang, replace in patterns:
        for m in pat.finditer(text):
            # Avoid replacing unrelated reader/fixture passwords.
            nearby = text[max(0, m.start()-1100):min(len(text), m.end()+300)]
            if ADMIN_EMAIL not in nearby:
                continue
            lineno = text.count('\n', 0, m.start()) + 1
            found.append((m.start(), m.end(), lineno, lang, replace(m)))
    return sorted(found, key=lambda x: x[0])


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument('--root', type=Path, default=Path.cwd(), help='Paper Haven project directory')
    ap.add_argument('--apply', action='store_true', help='Backup and replace only confident Admin QA literals')
    args = ap.parse_args()
    root = args.root.resolve()
    if not (root / 'bootstrap' / 'app.php').is_file() or not (root / 'app').is_dir():
        print('ERROR: Run from Paper Haven project root.', file=sys.stderr)
        return 2
    targets = []
    logs_with_literals = []
    for file, rel in safe_files(root):
        try:
            text = file.read_text(encoding='utf-8')
        except (OSError, UnicodeError):
            continue
        issues = matches(text, rel)
        if Path(rel).suffix.lower() in {'.txt', '.md'} and ADMIN_EMAIL in text:
            lines = text.splitlines()
            flagged = [i for i, line in enumerate(lines, 1)
                       if ('password' in line.lower() or 'mật khẩu' in line.lower())
                       and re.search(r'[A-Za-z0-9_+/-]{30,}', line)
                       and not re.search(r'(?i)(placeholder|change.me|your_|replace|<[^>]+>|private.env|env.only|do.not.share)', line)]
            if flagged:
                logs_with_literals.append((rel, flagged))
        if issues:
            targets.append((file, rel, text, issues))
    print(f'Potential Admin plaintext login matches: {sum(len(t[3]) for t in targets)}')
    for _, rel, _, issues in targets:
        print(f'  {rel}: line(s) {", ".join(str(i[2]) for i in issues)} [values hidden]')
    if logs_with_literals:
        print('Potential exposed credentials in text logs (manual redaction needed):')
        for rel, lines in logs_with_literals:
            print(f'  {rel}: line(s) {", ".join(map(str, lines[:12]))} [values hidden]')
    if (root / 'storage' / 'http_qa.json').exists():
        print('Sensitive local QA fixture detected at storage/http_qa.json (must stay untracked).')
    print('Reminder: terminal history / Antigravity conversations cannot be cleaned by a source patch.')
    print('Rotate the old Admin password in the authenticated application UI.')
    if not args.apply:
        print('Read-only scan complete. Use --apply to repair only high-confidence matching scripts.')
        return 1 if targets else 0
    if not targets:
        print('No matching scripts to modify; no files changed.')
        return 0
    desktop = Path.home() / 'OneDrive' / 'Desktop'
    if not desktop.is_dir():
        desktop = Path.home() / 'Desktop'
    if not desktop.is_dir():
        desktop = root.parent
    backup = desktop / ('PaperHaven_Credential_Backup_' + dt.datetime.now().strftime('%Y%m%d_%H%M%S'))
    backup.mkdir(parents=True, exist_ok=False)
    for file, rel, source, issues in targets:
        dest = backup / rel
        dest.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(file, dest)
        fixed = source
        for start, end, _, _, replacement in reversed(issues):
            fixed = fixed[:start] + replacement + fixed[end:]
        if file.suffix.lower() == '.py' and 'os.environ[\'' + ENV_KEY + '\']' in fixed:
            # Ensure an import exists; insert after the optional future statement.
            if not re.search(r'(?m)^\s*(?:import os|from os import environ)\s*$', fixed):
                pos = 0
                future = re.search(r'(?m)^from __future__ import [^\n]+\n', fixed)
                if future:
                    pos = future.end()
                elif fixed.startswith('#!'):
                    pos = fixed.index('\n') + 1
                fixed = fixed[:pos] + 'import os\n' + fixed[pos:]
        file.write_text(fixed, encoding='utf-8')
        print(f'REPAIRED: {rel}; original stored in private local backup.')
    print(f'BACKUP (contains old secrets; restrict access and remove after verification): {backup}')
    print(f'Set {ENV_KEY} only for local QA sessions; do not save its value in source or docs.')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
