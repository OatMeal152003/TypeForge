# VS Code Copilot Custom Instructions : typeforge

> Governed by Sauron v1.0.0. Universal AI agent harness.
> Applied automatically by GitHub Copilot via VS Code instruction files standard.

## Coding and Architectural Directives

- Present tense, active voice, English language exclusively.
- Zero emojis across all code files, comments, and commit messages.
- Strict Red-Green-Refactor TDD required before touching production code.
- Zero hardcoded secrets, connection strings, or unredacted logging output.
- PHP 8 vanilla MPA + PDO prepared statements only. No ORM. Validate all boundaries.
- Token Conservation & Caveman Mode: When invoked with '/caveman' (lite/full/ultra), eliminate filler while preserving code, commands, paths verbatim.

## Fellowship Sub-Agents Context

- **Gandalf** (Master Planner): invoke via `/gandalf`
- **Aragorn** (Principal System Architect): invoke via `/aragorn`
- **Boromir** (Security Auditor and Shield): invoke via `/boromir`
- **Frodo** (Ringbearer and Core Task Executor): invoke via `/frodo`
- **Samwise** (Git Commits and State Keeper): invoke via `/samwise`
- **Merry** (QA and TDD Specialist): invoke via `/merry`

## Local Skills - VSCode reads these first

- `.agents/skills/php-principles/SKILL.md`
- `.agents/skills/database-principles/SKILL.md`
- `.agents/skills/database-migration/SKILL.md`
- `.agents/skills/api-design/SKILL.md`
- `.agents/skills/security-best-practices/SKILL.md`
- `.agents/skills/sast-static-code-scanner/SKILL.md`
- `.agents/skills/agent-guard/SKILL.md`
- `.agents/skills/owasp-asvs-verification-gate/SKILL.md`
- `.agents/skills/javascript-principles/SKILL.md`
- `.agents/skills/tailwind-principles/SKILL.md`
- `.agents/skills/accessibility-auditor/SKILL.md`
- `.agents/skills/write-a-test/SKILL.md`
- `.agents/skills/performance-principles/SKILL.md`

## Prohibited Anti-Patterns

- AP-1 (Vague task verb): Always decompose requests into concrete atomic tasks.
- AP-6 (Monolithic prompt): Never combine architecture, code, and test in one step.
- AP-14 (Leaking secrets): Zero credentials in version control.
- AP-17 (Skipping tests): Code without a prior failing test is unverified code.
- AP-18 (Non-atomic commit): One logical concern per commit.
- AP-52 (Fake fix): Masking errors with type casts or empty catch blocks is prohibited.
