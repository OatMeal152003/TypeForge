---
name: sast-static-code-scanner
description: Static application security testing for tainted input sink tracing, SQL injection patterns, and unsafe deserialization.
department: security
ownerAgent: boromir
triggerCommand: /sast-static-code-scanner
antiPatternsPrevented:
  - AP-1
  - AP-6
  - AP-18
  - AP-26
---

# SAST Static Code Scanner

## 0. Identity

- **Role:** Application Security Code Reviewer. Audits source code for tainted data flow, unsafe sinks, and injection vulnerabilities.
- **Authority:** Normative specification under `skills/security/sast-static-code-scanner/`.
- **Must not define:** Application visual appearance.
- **Normative base:** `core/fellowship/boromir.md`, `rules/engineering/architecture-boundaries.md`, `rules/common/code-style-standards.md`, `references/anti-patterns.md`, `rules/security/owasp-defensive-shield.md`, `context/core-domains/security-policies.md`.

## 1. Intent (9 Dimensions)

| #   | Dimension        | Value                                                                                        |
| --- | ---------------- | -------------------------------------------------------------------------------------------- |
| 1   | Task             | Scan source code repositories for security flaws, tainted data paths, and hardcoded secrets. |
| 2   | Target Tool      | Semgrep, SonarQube, CodeQL, Gitleaks, ESLint Security.                                       |
| 3   | Output Format    | SARIF scan result reports, automated PR inline review comments, CI security gates.           |
| 4   | Constraints      | Ban unparameterized SQL queries, `eval()`, and raw shell command execution.                  |
| 5   | Input            | Source code repository files, PR diffs, Semgrep custom rule definitions.                     |
| 6   | Context          | Prevents introducing SQLi, RCE, SSRF, or credential leakage into the main branch.            |
| 7   | Audience         | AppSec engineers, code reviewers, and software developers.                                   |
| 8   | Success Criteria | Clean SARIF report, zero true-positive vulnerabilities merged into production.               |
| 9   | Examples         | See Section 5.                                                                               |

## 2. Scan Directives

1. **Taint Tracking:** Follow untrusted user inputs from controllers down to database drivers and shell execution calls.
2. **Block Unsafe Sinks:** Ban dynamic string evaluation (`eval()`), raw shell execution (`child_process.exec()`), and unparameterized SQL queries.
3. **Fail-Closed CI Gate:** Fail CI build pipelines immediately upon detection of any High or Critical severity vulnerability.
4. **Zero Inline Ignores Without Justification:** Any `// nosec` or `// semgrep-ignore` comment must include a mandatory reason code and ticket ID.

## 3. Semgrep Custom Rule Example

```yaml
rules:
  - id: no-raw-exec-injection
    patterns:
      - pattern: child_process.exec($CMD, ...)
      - pattern-not: child_process.exec("...", ...)
    message: "Potential Command Injection detected. Use child_process.execFile with argument array instead of exec with dynamic strings."
    severity: ERROR
    languages: [javascript, typescript]

  - id: no-string-concat-sql
    pattern: $DB.query(`SELECT ... ${$INPUT} ...`)
    message: "SQL Injection risk detected. Always utilize parameterized queries or prepared statements."
    severity: ERROR
    languages: [javascript, typescript]
```
