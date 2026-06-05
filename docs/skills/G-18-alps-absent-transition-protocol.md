---
layout: default
title: "G-18: ALPS-absent transition discovery protocol"
---

# G-18: ALPS-absent transition discovery protocol

## Context

Discovered during Wave 3 (`goForgotPassword` skipped) and Wave 4 (`doAdminLogin` / `doAdminLogout` invented), and used as a routine recovery path through Wave 5/6. Implementation agents working from an ALPS profile periodically discover that a transition they need to implement does not exist in `alps.json` — either because the upstream ALPS author missed it, or because a sibling transition exists but the form-show / entry transition was overlooked.

## Problem

When an agent discovers a missing ALPS transition mid-implementation, three obvious options are all wrong:

1. **Skip silently** — the migration ends up with a gap nobody flags.
2. **Edit `alps.json` directly** — the agent guesses at the descriptor schema, tag taxonomy, and naming convention; the ALPS file diverges from the authoritative source-of-truth in inconsistent ways across multiple parallel agents.
3. **Block on the orchestrator** — every parallel agent stalls waiting for an ALPS decision, defeating the parallelism.

The right answer is a **division of responsibility**: agents make a forward-progress decision, then surface the ALPS gap; the orchestrator owns ALPS coherence and back-fills.

## Solution / Convention

**Agents implement under a conventional name; the orchestrator backfills `alps.json`.**

Three-step protocol:

1. **Agent**: implement the missing transition using a **conventional name** derived from sibling transitions (`doAdminLogin` mirrors `doLogin`; `goForgotPassword` mirrors `goLogin`). Conventional naming rules:
   - Verb prefix matches the action type (`go*` form-show, `do*` state-changing).
   - Suffix reuses the closest sibling's noun form.
   - lowerCamelCase, matching ALPS descriptor-id convention.
2. **Agent**: report the gap with three explicit pieces of information:
   - A docblock on the Resource class noting "ALPS descriptor not present at implementation time, conventional name used".
   - The implementer's return message lists the gap under a "for orchestrator" section.
   - The fixture / test names use the conventional name so a later ALPS-side rename is mechanically traceable.
3. **Orchestrator**: after the wave merges, edit `alps.json` to register the transition with the conventional name. Verify JSON validity (`php -r 'json_decode(file_get_contents("alps.json"), true);'`) and re-run `asd --lint alps.json`.

**Critical**: agents do not edit `alps.json`. This is the one file the orchestrator owns alone. Parallel agents editing it would race and the resulting merge would be inconsistent across descriptors.

A second variant: when the missing transition is **out of scope** (the agent's brief said "implement go*X*", and `goX` does not exist), the agent **does not invent it**. The agent skips and reports — the orchestrator decides whether to add it to a later wave or to register it in ALPS without a Resource. This was the Wave 3 `goForgotPassword` resolution.

## Code example

```php
// src/Resource/Page/Admin/Login.php  (Wave 4)

/**
 * Admin login.
 *
 * NOTE: at implementation time (Wave 4) ALPS did not register
 * `doAdminLogin`. Member-CRUD admin transitions exist, but the admin's
 * own auth/logout transitions were missing. The conventional name
 * `doAdminLogin` was chosen to mirror `doLogin` (customer-side).
 * Orchestrator backfilled the ALPS descriptor post-wave; if you read
 * this comment after that backfill, the gap is closed.
 */
final class Login extends ResourceObject
{
    // ...
}
```

The agent's return message included:

```text
ALPS gaps found during implementation:
  - doAdminLogin   : not present in alps.json; implemented with conventional name
  - doAdminLogout  : same; mirrors doLogout

Orchestrator action: add both descriptors with actor-admin tag.
loginId + password descriptors are reused from the admin AAA infrastructure.
```

The orchestrator-side backfill:

```jsonc
// alps.json (after Wave 4)
{
  "id": "doAdminLogin",
  "type": "unsafe",
  "title": "Admin login",
  "descriptor": [{ "href": "#loginId" }, { "href": "#password" }],
  "tag": ["actor-admin", "src-controller"]
}
```

## Anti-pattern

**Agent edits `alps.json` directly:**

```jsonc
// Agent A's worktree adds:
{ "id": "doAdminLogin", "type": "unsafe", "tag": ["admin"] }

// Agent B's worktree, working in parallel on a sibling admin transition, adds:
{ "id": "doAdminLogin", "type": "unsafe", "tag": ["actor-admin", "auth"] }
```

Merge produces either a conflict, a duplicate descriptor, or a silently-chosen-wrong tag set. The fix involves un-doing both edits and re-doing it once with consistent taxonomy.

**Agent skips silently:**

```php
// "couldn't find ALPS descriptor, moving on"
// No docblock, no return-message entry.
```

The gap stays open forever; nobody knows it was a known omission rather than an unprocessed transition.

## Where this matters

- Multi-agent parallel migration where ALPS is the source of truth.
- Any pipeline where a generated artifact (ALPS, OpenAPI, JSON Schema) is authoritative but agents may discover the artifact is incomplete.
- The conventional-naming step is what lets the orchestrator backfill mechanically — the agent's name choice becomes the canonical ALPS id.

## Related

- **G-19** — when the missing transition is in the admin AAA path, follow the parallel-firewall split (`SessionInterface` vs `AdminSessionInterface`). G-18 + G-19 together cover the Wave 4 admin-bootstrap case.
