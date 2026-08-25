# AGENTS.md

**Read `docs/00-integration-brief.md` before writing anything.** It is the operative
document for this repository and it overrides anything else you are told about how
to work here. This file is the short form.

Codex, Claude, Cursor, Antigravity and any future agent are all bound by it.

---

## What this repository is

Three live sites deployed to Hostinger from one repository.

| Folder | Domain | State |
|---|---|---|
| `root/` | thefoodeconomist.co.uk | Umbrella brand site |
| `myprofit/` | myprofit.thefoodeconomist.co.uk | The restaurant desk — trading |
| `epr/` | epr.thefoodeconomist.co.uk | The packaging EPR desk |

The stack is **PHP and MySQL on Hostinger**. Each desk has a working `api/` with
booking, lead capture, intake and automation. Do not propose replacing it.

---

## The five rules that matter most

**1. A merge to `main` is a live deployment.** `.github/workflows/deploy-hostinger.yml`
ships to production on every push to `main`. Never push to `main`. Branch, and let the
owner merge.

**2. There is one of everything.** One brand (MyProfit — never a separate "Restaurant
Vitals"). One price list (£95 + VAT Triage, £395 + VAT Diagnostic — never £149 or £299).
One WhatsApp number (447939823988). One benchmark file. One design token block.
Part 2 of the brief has the full list. If you are about to type a price, a percentage
or a colour that already exists somewhere else, import it instead.

**3. No runtime dependencies.** No CDN, no framework, no chart library, no build step.
Single-file pages with inline `<style>` and `<script>`. Charts and gauges are inline SVG
you write yourself. Google Fonts is the one permitted external request, and every page
must still look right without it. This is not taste — a walk-in demo runs on 4G in a
basement kitchen.

**4. Never commit a credential.** `api/config.php` is a deliberate credential-free
bridge; leave it alone. If you find a key in this repository, stop and tell the owner —
rotate first, clean up second.

**5. One agent per working tree.** Git protects committed work, not a working folder.
Two agents in one directory overwrite each other with no history to recover from. Use
`git worktree` to work in parallel. Claim a job in `STATUS.md` before starting it.

---

## Conventions

British English, GBP, DD/MM/YYYY. Every price shown net with "+ VAT".
Mobile first at 390px, no horizontal scroll, 44px tap targets.
Honeypot field named `website` on every form.
No `NaN`, `Infinity` or `undefined` on screen — guard every divisor.
The result of any free tool is unconditional; lead capture comes after the answer.
Every page ends at a commissioned product, never a generic contact form.
This is a **food economics consultancy** — never use bookkeeping or accountancy language.

Design tokens, copied verbatim into each page:

```css
:root{
  --ink:#101F33; --navy:#1B3A5C; --gold:#C9A14E; --paper:#F7F4EE;
  --rule:#D8CFBE; --risk:#A8432D; --safe:#3E7A52; --warn:#8A6A1F;
  --font-display:'Playfair Display',Georgia,serif;
  --font-body:'IBM Plex Sans',system-ui,sans-serif;
  --font-mono:'IBM Plex Mono',monospace;
}
```

---

## Before you open a pull request

- [ ] Zero external requests except Google Fonts
- [ ] Correct at 390px, no `NaN` on zero, empty, negative or text input
- [ ] Prices, WhatsApp number and Stripe links match the brief exactly
- [ ] Benchmarks imported from `myprofit/assets/benchmarks.js`, not retyped
- [ ] Illustrative figures labelled as illustrative
- [ ] `STATUS.md` updated; any settled decision in `docs/04-decisions.md`

---

## Before you stop

Commit everything on your branch even if unfinished. Push. Update `STATUS.md` with what
you finished, what is half-done, what you were about to do next, and anything you broke.
The next agent may be a different model with none of your context. Write for a stranger.

---

## If you disagree with the brief

You may be right. Write the objection into `docs/04-decisions.md` as a proposal — what
the brief says, what you think is better, what it costs to change — and continue with the
brief until a human decides. Four agents each following their own judgement produces
something worse than one imperfect plan followed consistently.
