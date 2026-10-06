# GitHub Copilot Custom Instructions for typeforge

> Sauron Universal Harness v1.0.0 - VSCode-focused, PHP 8 + MySQL + Vanilla JS MPA

## Coding and Architectural Guidelines

- Present tense, active voice, English language exclusively.
- Zero emojis across all code files, comments, and commit messages.
- Strict Red-Green-Refactor TDD required before touching production code.
- Zero untyped boundary parameters: enforce runtime validation in PHP (filter_var, explicit casts, PDO prepared statements only).
- Zero hardcoded secrets, connection strings, or unredacted logging output. Use getenv() with .env, never commit credentials.
- PHP 8.0+ only: typed properties, str_starts_with(), random_bytes(), password_hash(PASSWORD_DEFAULT), PDO with EMULATE_PREPARES=false.
- All file mutations must preserve existing behavior unless explicitly requested. No destructive overwrites.
- Token Conservation: when invoked with '/caveman', eliminate filler while preserving all code, commands, paths verbatim.

## Fellowship Sub-Agents and Roles

- **Gandalf** (Master Planner and Strategy Guide): /gandalf
- **Aragorn** (Principal System Architect): /aragorn
- **Boromir** (Security Auditor and Shield): /boromir
- **Frodo** (Ringbearer and Core Task Executor): /frodo
- **Samwise** (Git Commits and State Keeper): /samwise
- **Merry** (QA and TDD Specialist): /merry

## Available Local Skills (.agents/skills/)

Apply these on every relevant edit. Read SKILL.md before coding:

- `php-principles`: `.agents/skills/php-principles/SKILL.md` - no eval/exec, no pickle-equivalent unserialize on untrusted data, subprocess with arg lists, Path::resolve containment, PDO prepared only.
- `database-principles`: `.agents/skills/database-principles/SKILL.md` - schema changes via migrations, no root/'' fallback in prod.
- `database-migration`: `.agents/skills/database-migration/SKILL.md` - versioned SQL in docs/migrations/, seeds, mysqldump backups.
- `api-design`: `.agents/skills/api-design/SKILL.md` - unify api/*.php legacy shims with api/v1/index.php?r= router, keep openapi.yaml in sync.
- `security-best-practices`: `.agents/skills/security-best-practices/SKILL.md` - httponly/secure/samesite cookies, hash_equals CSRF, password_verify + needs_rehash, sha256(random_bytes) tokens.
- `sast-static-code-scanner`: `.agents/skills/sast-static-code-scanner/SKILL.md` - scan for eval, dynamic sinks, private keys, AWS keys before commit.
- `agent-guard`: `.agents/skills/agent-guard/SKILL.md` - block secret reads, destructive shell, unbacked overwrites.
- `owasp-asvs-verification-gate`: `.agents/skills/owasp-asvs-verification-gate/SKILL.md` - pre-merge auth/session/reset/upload gate, generic reset responses, real mailer.
- `javascript-principles`: `.agents/skills/javascript-principles/SKILL.md` - typing-engine.js fetch timeouts, no blocking in async, context managers for resources.
- `tailwind-principles`: `.agents/skills/tailwind-principles/SKILL.md` - pin CDN + SRI + local fallback, move off Play CDN for prod.
- `accessibility-auditor`: `.agents/skills/accessibility-auditor/SKILL.md` - 44px targets, h1-h6 order, :focus-visible, no clickable divs.
- `write-a-test`: `.agents/skills/write-a-test/SKILL.md` - failing test first for SessionService caps wpm<=350 raw<=400 cps<=40, 3s throttle.
- `performance-principles`: `.agents/skills/performance-principles/SKILL.md` - fix ORDER BY RAND(), cache COUNT(*)/AVG(*) on index.php, GROUP BY scans.

## Anti-Patterns and Prohibitions

- AP-1 (Vague task verb): Always decompose requests into concrete atomic tasks.
- AP-6 (Monolithic prompt): Never combine architecture, code, and test in one step.
- AP-14 (Leaking secrets): Zero credentials in version control.
- AP-17 (Skipping tests): Code without a prior failing test is unverified code.
- AP-18 (Non-atomic commit): One logical concern per commit.
- AP-52 (Fake fix): Masking errors with type casts or empty catch blocks is prohibited.
- TypeForge-specific: never output die(json_encode()) on HTML pages, always sanitize() echo, never trust client wpm/accuracy, recompute server-side.
