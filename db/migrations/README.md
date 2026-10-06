# TypeForge DB Migrations

Baseline: `001_baseline_schema.sql` dumped with `mysqldump --no-data` from XAMPP MySQL 2026-10-01.

Rules per `.agents/skills/database-migration/SKILL.md` + `database-principles/SKILL.md`:
- New change = new file `002_<short-name>.sql`, never edit `001`.
- Breaking change (rename/drop, NOT NULL without default): use Expand-Contract 5-phase, get explicit approval before running on prod.
- New column on populated table: nullable or with DEFAULT.
- Every FK column must have an index. No table-locking DDL on large tables.
- Verify with `EXPLAIN` for slow paths, run in transaction where possible.

Apply (dev):
```
C:\xampp\mysql\bin\mysql.exe -h 127.0.0.1 -P 3306 -u root typeforge < db\migrations\002_example.sql
```

Backup before any migration:
```
.\scripts\backup-db.ps1
```
