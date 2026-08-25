# DECISION LOG

Every settled question, newest first. An agent that disagrees writes a proposal here
rather than quietly doing something different.

Format: what was decided, when, why, what it rules out.

---

## D-008 — One agent per working tree; branch per job
**25/08/2026 — settled**
Git protects committed work, not a working folder. Concurrent agents in one directory
overwrite each other unrecoverably. Agents take whole jobs, work on branches, merge by
pull request; parallel work uses `git worktree`.
Rules out: file-level task splitting, agents sharing a folder, pushing to `main`.

## D-007 — `myprofit/assets/benchmarks.js` is the only source of benchmark figures
**25/08/2026 — settled**
A free tool and a paid report that disagree on a number destroy the practice's
credibility. Every calculator imports the same file.
Rules out: retyping a percentage into a second file.

## D-006 — MyProfit is the brand; "Vitals" is a feature name
**25/08/2026 — settled**
A standalone "Restaurant Vitals" brand reads as a software product and pulls against the
food economics positioning. It would also split search authority from an established
domain.
Rules out: a separate brand, site or domain; buying restaurantvitals.co.uk.

## D-005 — The live prices are the only prices
**25/08/2026 — settled**
£95 + VAT Triage, credited against £395 + VAT Diagnostic. Earlier plans proposed
£149/£199/£349 and a "14-day trial"; those were written without knowledge of the live
products and are superseded.
Rules out: £149, £249, £299, £449, "guaranteed savings or you don't pay".

## D-004 — Keep the PHP and MySQL back end
**25/08/2026 — settled**
An earlier recommendation to move to a static site on Supabase was made without knowing
a working PHP API existed, holding live bookings. Replacing it would be destruction
dressed as progress.
Rules out: Supabase, Firebase, Node, any framework or build step on the desks.

## D-003 — The live data engine is the business; the sites are its front door
**25/08/2026 — settled**
One-off reports are hours sold and stop when the founder stops. The engine — invoices
arriving automatically, prices tracked line by line, benchmarked across clients —
compounds, is defensible, and makes the reports faster. See `02-the-engine.md`.

## D-002 — Prospect data comes from the FSA register, not a paid database
**25/08/2026 — settled**
Apollo and similar tools cover owner-operated hospitality poorly. The Food Standards
Agency publishes every registered UK food business free and daily, with business type
and coordinates.
Rules out: paid lead databases, bought lists.

## D-001 — Positioning is food economics consultancy
**25/08/2026 — settled**
The practice analyses operating information and advises on cost, supply, menu and price.
It is not bookkeeping or accountancy, and the language must hold that line.
Rules out: accounting-software exports, maintaining client records as a service,
bookkeeping vocabulary anywhere in the interface.

---

## PROPOSALS — open, awaiting the owner

*(none yet — add yours here rather than acting on it)*
