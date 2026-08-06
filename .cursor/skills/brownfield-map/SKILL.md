---
name: brownfield-map
description: >
  Maps and describes existing PHP/legacy behavior from evidence (entry points,
  collaborators, boundaries, side effects, risks, unknowns). Use when the user
  asks to map a legacy flow, document observed backend behavior, or build a
  baseline understanding before a change—without implementing code or writing tests.
---

# Brownfield Map

## Intent
Describe existing behavior as observed. Analysis and baseline understanding only.

## Activation examples
- "map this legacy flow before refactoring"
- "document observed backend behavior before a change"
- "what does this module actually do today?"

## Scope — describe only

**Allowed**
- Read code, configs, existing tests, contracts, and user input as evidence
- Produce structured analysis in the response

**Not allowed**
- Implement, refactor, or otherwise change production code
- Write or edit tests
- Treat the map as an executable freeze of behavior
- Invent behavior not supported by evidence

## Core Requirements
- Do not invent behavior; use evidence from code, existing tests, contracts, and user input.
- Preserve current behavior in the description first, even if odd.
- Separate observed behavior from assumptions and open questions.
- Prioritize fragile and side-effect-heavy paths in the description.

## Workflow
1. Gather context across relevant artifacts (code, config, existing tests, contracts, user statements).
2. Map legacy behavior: entry points, collaborators, external boundaries, side effects.
3. Call out fragile / high-risk paths (normal, edge, failure, legacy quirk) as descriptive risk notes—not as work to implement.
4. Record gaps: what cannot be verified from evidence yet.

## Required Output
1. **Context Summary** — where the behavior lives and what artifacts were used
2. **Behavior Map** — observed facts with evidence pointers (files/symbols), not speculation
3. **Constraints / Unknowns** — limits of the evidence; open questions
4. **Fragile / High-Risk Paths** — descriptive only (what is brittle or side-effect heavy)
5. **Notes / Gaps** — assumptions explicitly labeled; automation or verification gaps
6. **Confidence**
   - High / Medium / Low
   - Why this confidence level was assigned
