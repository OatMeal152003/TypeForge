---
name: owasp-asvs-verification-gate
description: Automated verification against Application Security Verification Standard (ASVS) Level 2 and Level 3 controls.
department: security
ownerAgent: boromir
triggerCommand: /owasp-asvs-verification-gate
antiPatternsPrevented:
  - AP-1
  - AP-6
  - AP-18
  - AP-26
---

# OWASP ASVS Verification Gate

## 0. Identity

- **Role:** Principal Security Auditor. Audits application architecture and code against OWASP ASVS verification controls.
- **Authority:** Normative specification under `skills/security/owasp-asvs-verification-gate/`.
- **Must not define:** Application UI layouts or stylistic choices.
- **Normative base:** `core/fellowship/boromir.md`, `rules/engineering/architecture-boundaries.md`, `rules/common/code-style-standards.md`, `references/anti-patterns.md`, `rules/security/owasp-defensive-shield.md`, `context/core-domains/security-policies.md`.

## 1. Intent (9 Dimensions)

| #   | Dimension        | Value                                                                                         |
| --- | ---------------- | --------------------------------------------------------------------------------------------- |
| 1   | Task             | Audit and enforce OWASP ASVS Level 2 security controls across application architectures.      |
| 2   | Target Tool      | OWASP ASVS v4.0 Checklist, ZAP, Semgrep, Trivy.                                               |
| 3   | Output Format    | Security verification matrices, remediation task tickets, signed audit approvals.             |
| 4   | Constraints      | Zero open High or Critical findings allowed in release builds. Enforce deny-by-default.       |
| 5   | Input            | Authentication flows, session storage specs, cryptographic configurations, API schemas.       |
| 6   | Context          | Prevents authentication bypasses, broken access control, and regulatory non-compliance.       |
| 7   | Audience         | Security champions, lead backend engineers, and compliance officers.                          |
| 8   | Success Criteria | 100% compliance with ASVS Level 2 mandatory controls verified prior to production deployment. |
| 9   | Examples         | See Section 5.                                                                                |

## 2. Verification Controls (ASVS L2)

1. **Authentication & Session:** Enforce rate-limiting on credential endpoints, cryptographically random session IDs, and secure cookie flags (`HttpOnly`, `Secure`, `SameSite=Lax`).
2. **Access Control Enforcement:** Implement deny-by-default access control lists at controller boundaries. Verify tenant isolation on every SQL query.
3. **Data Protection:** Ban unencrypted storage of sensitive data. Enforce TLS 1.3 for all external and inter-service transport channels.
4. **Input Validation & Encoding:** Validate input length, format, and range. Contextually encode all outputs to prevent Cross-Site Scripting (XSS).

## 3. Implementation Verification Checklist

```typescript
export function assertSecureSessionCookie(res: Response, token: string): void {
  res.cookie("session_id", token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    maxAge: 3600 * 1000, // 1 hour expiration
  });
}

export function verifyTenantOwnership(
  userId: string,
  targetTenantId: string,
): void {
  if (!userHasAccessToTenant(userId, targetTenantId)) {
    throw new ForbiddenException(
      "Access denied: Tenant isolation policy violation (ASVS V4.1)",
    );
  }
}
```
