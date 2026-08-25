# STATUS

**The job board. Every agent reads this first and updates it before stopping.**

Last updated: 25/08/2026 — Claude, initial board

Full detail for every job is in `docs/00-integration-brief.md`, Part 5.

---

## RULES FOR THIS FILE

- **Claim a job before you start.** Put your agent name and the date in Owner.
- **Release it when your pull request is merged.** Set Owner back to `free`.
- **Never take a job another agent holds.** If it has been stale more than seven days,
  say so in the log below, then take it.
- **Add a log entry every session**, however short. Especially if you left something broken.

---

## JOB BOARD

| # | Job | Branch | Owner | State |
|---|---|---|---|---|
| 0 | Agent contract, docs folder, this board | `docs/agent-contract` | free | not started |
| 1 | Publish the Vitals Wheel (file already written) | `feature/vitals-wheel` | free | file supplied, not committed |
| 2 | Extract the single benchmark file | `feature/benchmarks-single-source` | free | not started |
| 3 | Sitemaps and internal linking pass | `fix/sitemaps-and-linking` | free | not started |
| 4 | Delivery Commission Calculator | `feature/delivery-commission-calculator` | free | not started |
| 5 | Menu Dish GP Calculator | `feature/menu-gp-calculator` | free | not started |
| 6 | EPR Fee Estimator | `feature/epr-estimator` | free | not started |
| 7 | Price capture from paid reports | `feature/price-capture` | free | not started |
| 8 | The ingestion engine | — | free | **not specified — ask the owner first** |

Suggested order: 0, 1, 2, 3, 4. Job 8 is the business; do not start it from a summary.

---

## OPEN QUESTIONS FOR THE OWNER

Things an agent must not decide alone.

- [ ] Is branch protection enabled on `main`? A merge deploys to production.
- [ ] Should `vitals.html` stay `noindex`, or be indexed once tested?
- [ ] Which four London boroughs to pull first for the FSA prospect list?
- [ ] Confirm the aggregation clause wording for `myprofit/terms.html` (Job 7).

---

## LOG

Newest first. Short and honest.

### 25/08/2026 — Claude (Cowork)
Read the whole repository. Wrote `docs/00-integration-brief.md`, `AGENTS.md` and this
board. Built `myprofit/vitals.html` complete and tested it in a mobile browser at 390px —
worked example reproduces £4,150/month, £49,800/year, Heart 61, Lungs 56; no `NaN` on
zero or negative input; no horizontal scroll; no CDN.

Could not push: this session's git proxy has no credential for the repository. The
`vitals.html` file and a patch of the commit were handed to the owner directly.

**Next:** Job 0, then Job 1.

**Nothing is broken.**
