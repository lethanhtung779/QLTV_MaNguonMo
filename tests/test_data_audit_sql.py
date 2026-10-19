#!/usr/bin/env python3
"""Offline minimal classification regression; no live DB required."""
from __future__ import annotations
import pathlib
import re
import sqlite3

php = (pathlib.Path(__file__).parent / 'audit_data_provenance_readonly.php').read_text(encoding='utf-8')
queries = re.findall(r"'(books|readers|loan_items|copies)'\s*=>\s*<<<'SQL'\n(.*?)\nSQL,", php, re.S)
assert len(queries) == 4, f'Expected 4 read-only SQL queries; got {len(queries)}'
db = sqlite3.connect(':memory:')
db.row_factory = sqlite3.Row
db.executescript('''
CREATE TABLE books (id INTEGER, book_code TEXT, isbn TEXT, cover_url TEXT);
CREATE TABLE readers (id INTEGER, card_number TEXT);
CREATE TABLE book_copies (id INTEGER, book_id INTEGER, status TEXT);
CREATE TABLE loans (id INTEGER, reader_id INTEGER, note TEXT);
CREATE TABLE loan_items (id INTEGER, loan_id INTEGER, book_copy_id INTEGER);
INSERT INTO books VALUES (1,'DEMO-B01',NULL,NULL),(2,'AUTH-01',NULL,'real-cover.jpg'),(3,'B001','9780000000001',NULL);
INSERT INTO readers VALUES (1,'DEMO-READ-01'),(2,'READ-02');
INSERT INTO book_copies VALUES (1,1,'available'),(2,2,'pending_audit'),(3,3,'available');
INSERT INTO loans VALUES (1,1,'DEMO_FIXTURE:x'),(2,2,NULL),(3,2,'DEMO_FIXTURE:y');
INSERT INTO loan_items VALUES (1,1,1),(2,2,2),(3,2,3),(4,3,2);
''')
results = {}
for label, query in queries:
    assert query.strip().upper().startswith('SELECT'), 'Only SELECT allowed'
    result = dict(db.execute(query).fetchone())
    results[label] = result
assert results['books']['total'] == 3 and results['books']['tagged_demo'] == 1
assert results['books']['not_tagged_demo'] == 2 and results['books']['non_demo_without_isbn'] == 1
assert results['readers']['tagged_demo'] == 1 and results['readers']['not_tagged_demo'] == 1
assert results['loan_items']['tagged_demo'] == 2 and results['loan_items']['not_tagged_demo'] == 2
assert results['copies']['non_demo_pending_audit'] == 1 and results['copies']['non_demo_available'] == 1
print('PASS: 4 read-only SQL queries and seeded fixture/non-fixture classification')
