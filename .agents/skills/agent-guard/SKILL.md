---
name: agent-guard
description: Pre-execution safety hook, destructive command interceptor, and credential shield for autonomous AI coding agents across Claude Code and Copilot CLI.
origin: sauron
department: security
---

# Agent Guard: Autonomous Safety Hook and Command Interceptor

Intercept, evaluate, and gate autonomous agent tool calls and shell commands before execution. Block destructive filesystem wipes, irreversible git history rewrites, and credential exposure.

## When to Activate

- Configuring pre-execution safety hooks for Claude Code or GitHub Copilot CLI.
- Auditing autonomous agent permission models and shell access boundaries.
- Preventing automated accidental execution of `git push --force`, `rm -rf`, or database drops.
- Enforcing human confirmation gates for high-risk operations.

## Core Concepts

### 1. Interception Levels and Policy Matrix

Every tool call or shell command executed by an autonomous coding agent must pass through this evaluation matrix:

| Action Category          | Target Commands / Patterns                                                     | Guard Policy          | Rationale                                                                    |
| :----------------------- | :----------------------------------------------------------------------------- | :-------------------- | :--------------------------------------------------------------------------- |
| **Credential Access**    | Reading or modifying `.env`, `.env.*`, `*credentials*`, `*id_rsa*`, `*secret*` | **HARD BLOCK**        | Secrets must never leak into LLM context windows or logs.                    |
| **Destructive Git**      | `git push --force`, `git push -f`, `git reset --hard`, `git rebase -i`         | **HARD BLOCK**        | Overwriting shared upstream git history causes permanent teammate data loss. |
| **Filesystem Wipe**      | `rm -rf /`, `rm -rf ~`, `rm -rf .`, `rmdir /s /q C:\`                          | **HARD BLOCK**        | Monolithic recursive wipes cause irreversible workspace corruption.          |
| **Database Destruction** | `DROP DATABASE`, `DROP TABLE`, `TRUNCATE TABLE`, `prisma migrate reset`        | **HARD BLOCK**        | Production data loss must require explicit manual operator invocation.       |
| **Moderate Mutations**   | Single file deletion (`rm <file>`), `git clean -fd`, `git branch -D`           | **CONFIRMATION GATE** | Prompts operator with interactive confirmation before running.               |
| **Safe Read / Build**    | `git status`, `git diff`, `npm test`, `cargo check`, reading source files      | **AUTO-PERMIT**       | Harmless deterministic actions execute without friction.                     |

### 2. Hook Architecture (Claude Code vs Copilot CLI)

```text
Autonomous Agent Request
          │
          ▼
┌─────────────────────────────────┐
│   Sauron Agent Guard Hook       │
│  (.claude/hooks/guard.sh)       │
│  (.github/hooks/agent-guard.js) │
└─────────────────────────────────┘
          │
    ┌─────┴─────────────────────┐
    ▼                           ▼
[Forbidden Command]     [Permitted Command]
    │                           │
    ▼                           ▼
Exit Code 1 (Blocked)   Exit Code 0 (Execute)
```

## Security Directives

1. **Fail-Closed Default:** If the guard script encounters an unexpected syntax error or unhandled exception during pattern matching, it must exit with code 1 (fail-closed) to prevent unauthorized execution.
2. **Deterministic String Matching:** Strip leading whitespace, quotes, and case differences before matching commands against the blocklist.
3. **No Interactive Bypass:** In headless CI environments, moderate actions that require operator confirmation must fail gracefully rather than hanging the execution pipeline indefinitely.

## Code Examples

### POSIX Shell Guard Hook (`.claude/hooks/guard.sh`)

```bash
#!/usr/bin/env bash
set -euo pipefail

# Ingest proposed tool command from environment or argument
COMMAND="${1:-}"

if [ -z "${COMMAND}" ]; then
  exit 0
fi

# 1. Hard block credentials access
if echo "${COMMAND}" | grep -E -i '(\.env|id_rsa|id_ed25519|credentials\.json|\.pem|\.key)' >/dev/null; then
  printf "[AGENT GUARD] BLOCKED: Command attempts to access credentials or environment secret files.\n" >&2
  exit 1
fi

# 2. Hard block destructive git commands
if echo "${COMMAND}" | grep -E -i '(git\s+push\s+.*(--force|-f)|git\s+reset\s+--hard)' >/dev/null; then
  printf "[AGENT GUARD] BLOCKED: Destructive git command detected.\n" >&2
  exit 1
fi

# 3. Hard block recursive filesystem wipes
if echo "${COMMAND}" | grep -E -i '(rm\s+-[a-zA-Z]*r[a-zA-Z]*f\s+[/~.]|rmdir\s+/s\s+/q)' >/dev/null; then
  printf "[AGENT GUARD] BLOCKED: Dangerous recursive filesystem deletion detected.\n" >&2
  exit 1
fi

# Command passed all safety checks
exit 0
```

## Anti-Patterns

- **AP-14 (Leaking secrets):** Allowing agents to read `.env` and print secrets to LLM context.
- **AP-20 (Untracked work):** Agents resetting working trees without saving diff checkpoints.
- **AP-57 (Untracked side effect in CI):** Unsandboxed shell commands deleting dependencies or databases.

## Related Skills

- [security-audit](file:///C:/Users/IGING/Documents/GitHub/sauron/core/skills/security/security-audit.md)
- [secrets-scan](file:///C:/Users/IGING/Documents/GitHub/sauron/core/skills/security/secrets-scan.md)
- [docker-principles](file:///C:/Users/IGING/Documents/GitHub/sauron/skills/devops/docker-principles/SKILL.md)
