# THE FOOD ECONOMIST — INTEGRATION BRIEF
## For any agent working in `the-food-economist-live-sites`

**Written for:** Codex, Claude (desktop or Cowork), Cursor, Antigravity — any agent with this folder open.
**Written by:** Claude, from a full read of the repository at 25 August 2026.
**Purpose:** fold the business plan into the estate that already exists, without duplicating it, contradicting it, or making a mess of it.

> **Read this before writing anything.** Most of what the business plan describes is already built. The risk on this project is not missing features. It is **two of everything** — two brands, two prices, two calculators that disagree, two sets of benchmarks. This document exists to stop that.

---

# PART 1 — THE ESTATE AS IT ACTUALLY IS

Verified from the repository, not assumed.

## 1.1 Three sites, one repository

| Folder | Live domain | What it is |
|---|---|---|
| `root/` | `thefoodeconomist.co.uk` | Umbrella brand site. Thin — 10 files. |
| `myprofit/` | `myprofit.thefoodeconomist.co.uk` | **The restaurant desk. Trading.** |
| `epr/` | `epr.thefoodeconomist.co.uk` | The packaging EPR desk. Built. |

## 1.2 The stack is PHP and MySQL, not static

Each desk has a working server-side API:

```
myprofit/api/  and  epr/api/
  booking.php       slot booking, ICS invites, cancellation tokens, email
  automation.php    the automation layer
  save-lead.php     lead capture
  save-intake.php   intake capture
  schema.sql        MySQL schema
  config.php        credential-free bridge — real config lives outside the web root
  booking-data/     runtime data, git-ignored except .htaccess
```

**Do not propose replacing this with Supabase, Firebase, Node, or anything else.** It works, it is deployed, and it holds live bookings. New server-side work is PHP written in the same style. New client-side work needs no server at all.

## 1.3 Deployment: a merge to `main` is a live release

`.github/workflows/deploy-hostinger.yml` runs on every push to `main`. It tars `epr/`, `myprofit/` and the contents of `root/`, sends them over SSH, and unpacks them into the Hostinger document roots. It deliberately excludes `api/config.php` and `api/booking-data/`.

Three consequences that govern everything below:

1. **`main` is production.** Never push directly to it. Branch, then let the owner merge.
2. **Only `epr/`, `myprofit/` and `root/` are deployed.** Anything at the top level of the repository — `docs/`, `AGENTS.md`, `*.patch` — stays private and never reaches the web server. This is why planning documents belong at the top level and nowhere else.
3. **A new, unlinked page is low risk. An edit to an existing page is not.** Treat those two classes of work differently, always.

## 1.4 Security: already correct, do not disturb

`api/config.php` is committed as a three-line bridge that `require`s the real configuration from outside the web root. Real credentials never enter git. `.gitignore` handles `booking-data/` properly.

**Never put a credential in this repository.** If you find one, stop, tell the owner, and treat the key as leaked — rotate first, clean up second.

---

# PART 2 — THE SINGLE SOURCE OF TRUTH RULES

This is the heart of the brief. Every item below has exactly one correct value. Where an old document, an old plan, or another AI has told you something different, **the value here wins**.

## 2.1 One brand

**MyProfit is the brand for the restaurant desk. "Vitals" is the name of a feature within it, not a separate business.**

- Do not create a "Restaurant Vitals" brand, logo, domain, or standalone site.
- Do not register or link `restaurantvitals.co.uk`.
- The Vitals Wheel is a page on MyProfit. It says "MyProfit · The Food Economist" in its header and footer.
- Older documents referring to "Restaurant Vitals DFY" as a business are superseded. Read them for the commercial reasoning, not for naming.

## 2.2 One price list

These are the live prices. Nothing may contradict them.

| Product | Price | Notes |
|---|---|---|
| Restaurant Profit Check | Free | `myprofit/verdict.html`, no email required for the result |
| The Vitals Wheel | Free | `myprofit/vitals.html`, illustrative figures |
| 48-Hour Profit Triage Report | **£95 + VAT** | Written, 48 hours, from rough figures |
| Menu & Margin Diagnostic | **£395 + VAT** | Fixed fee. The £95 is credited in full against it within 30 days |
| Prepared MyProfit call | Booked, not priced on the page | via `api/booking.php` |
| EPR reports | **£295 – £1,250 + VAT** | epr desk |
| Partner referral | **£50** per paid Diagnostic | or white-label at a trade rate |

**Forbidden:** £149, £249, £299, £449, "14-day trial", "£500 guaranteed savings or you don't pay". These appear in earlier plans and in output from other AI tools. They are not live products and must never reach a page.

Every price is displayed **net, with "+ VAT"**, using the existing `data-net` / `.vat-gross` pattern from `myprofit/index.html`.

## 2.3 One set of contact details

| | Value |
|---|---|
| WhatsApp | `447939823988` — always via `https://wa.me/447939823988?text=...` |
| Email | `godfred@thefoodeconomist.co.uk` |
| Triage Stripe link | `https://buy.stripe.com/4gM4gy3TL2diaTw8rmcjS02` |
| Diagnostic Stripe link | `https://buy.stripe.com/8x2fZg1LD05zcdLGcjS03` |

Never invent a placeholder number. Never introduce a WhatsApp Business API integration — it costs per conversation and adds nothing at this scale.

## 2.4 One set of benchmarks

Every calculator on the estate must produce the same answer from the same inputs. A free tool that says one thing and a paid report that says another destroys the practice's credibility in a single afternoon.

**Create `myprofit/assets/benchmarks.js` as the only place benchmark percentages are defined.** Seven venue types, each with its cost lines, typical and target percentages, and the delivery and drinks share of sales. `vitals.html` already contains this data inline — lift it out into that file and have both `vitals.html` and any new calculator import it.

`verdict.html` works from figures the visitor types rather than from benchmarks, so it does not need to import them — but where it *shows* a target or a band, that target must match `benchmarks.js`.

**Rule: if you find yourself typing a percentage into a second file, stop and import it instead.**

## 2.5 One design token block

```css
:root{
  --ink:#101F33; --navy:#1B3A5C; --gold:#C9A14E; --paper:#F7F4EE;
  --rule:#D8CFBE; --risk:#A8432D; --safe:#3E7A52; --warn:#8A6A1F;
  --font-display:'Playfair Display',Georgia,serif;
  --font-body:'IBM Plex Sans',system-ui,sans-serif;
  --font-mono:'IBM Plex Mono',monospace;
}
```

Fonts are loaded from Google Fonts: `Playfair Display 700;800`, `IBM Plex Sans 400;500;600`, `IBM Plex Mono 400;500;600`.

The estate uses **single-file pages** — each page carries its own inline `<style>`. That means the token block is *repeated* per page, which is correct here. **Copy it verbatim. Never invent a colour. Never write a raw hex value outside this block.**

Forbidden: dark "slate" themes, Tailwind, Bootstrap, any CSS framework, any component library.

## 2.6 One positioning vocabulary

This is a **food economics consultancy**. It analyses operating information and advises on cost, supply, menu and price. It is not bookkeeping and not accountancy.

| Use | Never |
|---|---|
| Operating review, cost review | Management accounts |
| Cost analysis, variance review | Bookkeeping, data entry |
| Advisory note, written report | Financial statements |
| Cost lines | Nominal codes, ledger, chart of accounts |
| Your figures, your operating data | Your books, your records |
| Food economist, operations adviser | Accountant, bookkeeper, finance director |

Do not build an export formatted for accounting software. That single deliverable does more to blur the positioning than anything else.

Preserve the independence line wherever it appears: *"No compliance scheme, supplier, broker or software vendor pays us a commission."*

---

# PART 3 — WHERE THE BUSINESS PLAN LIVES

Create a top-level `docs/` folder. **It is not deployed** — the workflow only ships `epr/`, `myprofit/` and `root/` — so it is private working material even while the repository is public.

```
docs/
  00-integration-brief.md      this document — the operative one
  01-business-plan.md          commercial reasoning, pricing arithmetic, market
  02-the-engine.md             the live data platform: why it is the business
  03-repository-survey.md      what existed as at 25/08/2026
  04-decisions.md              decision log — every settled question, newest first
```

**Rules for `docs/`:**

- This brief is operative. The other documents are background. Where they disagree with this one, **this one wins** — they were written before the repository was read.
- Do not copy business-plan prose into page content. Pages get their own copy, written for a restaurant owner standing behind a counter, not for an investor.
- Every settled question goes in `04-decisions.md` as a dated entry: what was decided, why, and what it rules out. An agent that disagrees writes a proposal there rather than quietly doing something different.
- Never create a second copy of a planning document under a new name. Update the original.

---

# PART 4 — THE COMMERCIAL MODEL, IN ONE PAGE

So that anyone building knows what each page is for.

```
FREE PROFIT CHECK  (verdict.html)         the visitor's own figures, detailed
FREE VITALS WHEEL  (vitals.html)          illustrative, 60 seconds, for walk-ins
        │
        ▼
£95 + VAT  48-HOUR PROFIT TRIAGE          written, from their real invoices
        │                                  — and every one of these feeds the
        ▼                                    price database (see Job 7)
£395 + VAT MENU & MARGIN DIAGNOSTIC       dish level; the £95 is credited
        │
        ▼
THE LIVE ENGINE  (not built yet)          invoices arrive by email automatically,
                                          prices tracked line by line, benchmarked
                                          across clients, weekly reading, monthly fee
```

**The thing that makes this defensible** is the benchmark. No restaurant can know what comparable sites pay. Once invoices flow from twenty independents, the practice can say *"you are eight per cent over the London median on this line, and have been since March."* No accountant, no till and no software can produce that sentence. Every page should point toward it.

Every free tool ends at a commissioned product. Never at a generic contact form.

---

# PART 5 — THE BUILD QUEUE

In order. One branch per job. Do not start a job while another agent holds it — claim it in `STATUS.md` first.

---

### JOB 1 — Publish the Vitals Wheel *(file already written)*

**Branch:** `feature/vitals-wheel`
**File:** `myprofit/vitals.html` — supplied complete; add it, do not rewrite it.

Already correct in the supplied file: live tokens, £95 + VAT, live Stripe link, WhatsApp `447939823988`, no CDN, no framework, `noindex`, every figure captioned as illustrative, verified maths (£4,150/month, £49,800/year, Heart 61, Lungs 56 on the worked example), no `NaN` on zero or negative input, no horizontal scroll at 390px.

**Your only tasks:** commit it on a branch, and add nothing else to that branch.

**Do not** link it from `myprofit/index.html` yet, and **do not** add it to `sitemap.xml`. It is `noindex` on purpose, so the owner can test it live before anyone else can find it.

**Done when:** the branch exists and the owner has opened `myprofit.thefoodeconomist.co.uk/vitals.html` on a phone, on mobile data.

---

### JOB 2 — Extract the single benchmark file

**Branch:** `feature/benchmarks-single-source`
**File:** `myprofit/assets/benchmarks.js`

Lift the `T` (venue types) and action-text objects out of `vitals.html` into one exported module. Have `vitals.html` import it. Add a comment block at the top written for a non-programmer explaining how to change a percentage safely.

**Done when:** `vitals.html` behaves identically and no benchmark percentage appears in more than one file.

---

### JOB 3 — Sitemaps and internal linking

**Branch:** `fix/sitemaps-and-linking`

- `root/sitemap.xml` currently lists six URLs and omits both desks. Reference the desk sitemaps.
- `root/resources.html` links visitors to the **Beyondly** and **EP Group** EPR calculators. Leave the links for now, but note them for Job 6 — they are sending traffic to competitors.
- Check every desk's `robots.txt` and `sitemap.xml` agree with each other.
- Submit all three sitemaps in Search Console — an owner task, but flag it.

---

### JOB 4 — Delivery Commission Calculator

**Branch:** `feature/delivery-commission-calculator`
**File:** `myprofit/delivery-commission.html`

The strongest search intent in the sector and almost no serious UK competitor.

Inputs: weekly delivery sales, platform, commission rate, packaging cost per order, average order value.
Outputs: true contribution per delivery order after commission, packaging and food cost; the same for a direct order; the annual value of moving a stated percentage direct.

**Indexed**, unlike `vitals.html`. Needs 600+ words of genuine explanatory content beneath the tool or it will not rank, plus `WebApplication` and `FAQPage` JSON-LD, unique title under 60 characters and description under 155.

Ends at the £95 Triage.

---

### JOB 5 — Menu Dish GP Calculator

**Branch:** `feature/menu-gp-calculator`
**File:** `myprofit/menu-gp.html`

Up to eight ingredients per dish, ten dishes held in `localStorage` (wrapped in try/catch, correct when it returns nothing). After three dishes are costed, offer: *"You have costed three dishes. A full menu has forty. We do the whole menu and tell you which to promote, reprice, re-engineer or remove."* Ends at the **£395 Diagnostic**, not the Triage.

---

### JOB 6 — EPR Fee Estimator

**Branch:** `feature/epr-estimator`
**File:** `epr/fee-estimator.html`

Your own estimator, so `resources.html` stops sending prospects to Beyondly and EP Group. Given EPR reports run £295–£1,250 against £95–£395 on the restaurant side, this may be the highest-value page on the estate. Same build pattern; epr desk tokens and copy.

Update `root/resources.html` to lead with your own tool, keeping the official government links as references.

---

### JOB 7 — Start capturing prices from paid work *(owner process, not code, but build the store)*

**Branch:** `feature/price-capture`

Every £95 Triage and £395 Diagnostic already puts real invoices through the practice's hands, and that data currently leaves no trace. From now, line prices go into a structured store: date, supplier, item, unit, unit price, venue type, postcode district.

A spreadsheet is enough to begin. The table it seeds is the competitive position of the whole business, and it cannot be built retrospectively.

Add the aggregation clause to `myprofit/terms.html`: data is aggregated across a minimum number of sites, never attributed to any individual business, and used to improve the benchmarks every client sees.

---

### JOB 8 — The ingestion engine

Not yet specified. Client accounts, an invoice mailbox per client, a line-item parser, `invoice_lines` and `price_points` tables, a review console for the practitioner and a dashboard for the client. Built in PHP and MySQL as an extension of the existing `api/` pattern.

**Do not start this from a summary.** Ask the owner for the full engine specification first.

---

# PART 6 — THE DUPLICATION REGISTER

Every collision that will otherwise happen on this project, and the ruling on each.

| Collision | Ruling |
|---|---|
| `verdict.html` and `vitals.html` both "check profit" | **Different jobs, and they cross-link.** `verdict.html` = the visitor's own figures, detailed, indexed, the serious tool. `vitals.html` = illustrative, sixty seconds, `noindex`, for a walk-in on a phone. Each links to the other. Neither is retired. |
| Benchmarks in two files | One file: `myprofit/assets/benchmarks.js`. See 2.4. |
| Two prices for the first paid step | £95 + VAT. See 2.2. Delete any £149 on sight. |
| Two brands | MyProfit. See 2.1. |
| Design tokens re-invented per page | Copy the block in 2.5 verbatim. |
| A second "sample report" or "example" page | `myprofit/sample-report.html` exists. Link to it; do not write another. |
| A second booking mechanism | `api/booking.php` exists and works. Every "book a call" links to it. |
| A second lead form | `api/save-lead.php` exists. Reuse it, honeypot field named `website`. |
| A second privacy or terms page per desk | Each desk has its own. Update, never duplicate. |
| Business-plan prose pasted into a page | Pages get their own copy, written for an owner behind a counter. |
| A second partner or referral scheme | £50 per paid Diagnostic, or white-label at trade rate. One scheme. |
| A second contact number | `447939823988`. One number. |

---

# PART 7 — CONVENTIONS (taken from the existing code, not invented)

- **Single-file pages.** Each page carries its own inline `<style>` and `<script>`. No shared bundle on the desks. `root/` has `styles.css` and `script.js` — that is the umbrella site's own pattern; leave it.
- **No runtime dependencies.** No CDN, no framework, no chart library, no icon pack. Charts and gauges are inline SVG written by hand. This is not taste: a walk-in demo runs on 4G in a basement kitchen, and a CDN that fails renders your page as unstyled text in front of a prospect.
- **Google Fonts is the one permitted external request**, because the estate already uses it. Every page must still look right on its fallbacks.
- **British English. GBP. DD/MM/YYYY.**
- **Mobile first at 390px.** No horizontal scrolling anywhere. Tap targets 44px minimum. `inputmode="decimal"` or `"numeric"` on number fields. `clamp()` for headline type.
- **Accessibility.** Semantic HTML, correct heading order, ARIA labels on interactive controls, visible keyboard focus, colour never the only carrier of meaning — every red state also carries text.
- **Honeypot on every form**: a hidden text input named `website`, as the existing forms do.
- **Never `NaN`, `Infinity` or `undefined` on screen.** Guard every divisor. Zero, empty, negative and non-numeric input must all produce a sensible state.
- **The result is always free and unconditional.** Lead capture comes *after* the answer, never before it.
- **Cache-busting is manual** — `styles.css?v=5`. Increment it when CSS changes.
- **Every page ends at a commissioned product.**

---

# PART 8 — DEPLOY SAFETY

1. **Never push to `main`.** Branch, then the owner merges.
2. **Branch protection on `main` should be on.** If it is not, tell the owner before doing anything else.
3. **Classify your work before you start:**
   - *New, unlinked, `noindex` page* — low risk. Cannot break what does not reference it.
   - *Edit to an existing page, or to anything in `api/`* — high risk. Small diffs, explained, owner reviews.
4. **Never touch** `api/config.php` contents, `api/booking-data/`, or `.github/workflows/` without saying so first and explaining why.
5. **After a merge**, check the live URL with a cache-busting query string, as the repository README requires.
6. **`release.json` is a deployment marker**, not application configuration. Leave it alone unless releasing.

---

# PART 9 — DEFINITION OF DONE

A page is not finished until every line is true.

- [ ] Opens correctly from the filesystem, no server needed (client-side pages)
- [ ] Zero external requests except Google Fonts — check the network tab
- [ ] Renders correctly with fonts blocked
- [ ] No horizontal scroll at 390px; no tap target under 44px
- [ ] Zero, empty, negative and text input all handled; no `NaN` anywhere
- [ ] Keyboard-only navigation reaches every control, with visible focus
- [ ] British English throughout; every price net with "+ VAT"
- [ ] Prices, WhatsApp number and Stripe links match Part 2 exactly
- [ ] Benchmarks imported, not retyped
- [ ] Unique `<title>` under 60 chars and description under 155
- [ ] `noindex` if it is a demo; indexed with JSON-LD and 600+ words if it is a search asset
- [ ] Illustrative figures are labelled as illustrative
- [ ] Ends at a commissioned product
- [ ] `STATUS.md` updated
- [ ] Any settled decision recorded in `docs/04-decisions.md`

---

# PART 10 — WHAT NOT TO BUILD

Decline all of this until the stated trigger.

| Do not build | Until |
|---|---|
| A separate "Restaurant Vitals" site, brand or domain | Never |
| Client sign-up, billing pages, subscription management | 20 paying subscribers |
| WhatsApp Business API integration | Never at this scale |
| OCR or AI invoice extraction | The manual entry console exists and is genuinely the bottleneck |
| POS or bank feed integrations | A client asks and will pay |
| A mobile app | Never. The portal is a website. |
| A blog engine or CMS | Never. Pages are static and hand-edited. |
| Analytics, cookie banners, consent management | Never on the free tools. Without cookies, no banner is required. |
| Tailwind, React, Next.js, any framework or build step | Never on the desks |
| A second calculator that duplicates an existing one | Never |
| Supabase, Firebase, or any replacement for the PHP back end | Never |

---

# PART 11 — WORKING WITH OTHER AGENTS

Several agents contribute to this repository. Two rules keep it alive.

**One agent per working tree.** Git protects committed work; it does not protect a working folder. Two agents editing uncommitted files in the same directory will silently overwrite each other with no history to recover from. To work in parallel, give each agent its own directory:

```
git worktree add ../tfe-codex   feature/delivery-commission-calculator
git worktree add ../tfe-claude  feature/benchmarks-single-source
```

**One agent owns a whole job.** Claim it in `STATUS.md` before starting; release it when the pull request is merged. Do not split a job between agents — that is where mismatched conventions come from.

**Before you stop, whatever the reason:** commit everything on your branch even if unfinished, push, and update `STATUS.md` with what you finished, what is half-done, what you were about to do next, and anything you broke. The next agent may be a different model with none of your context. Write for a stranger.

**If you disagree with this brief**, you may well be right. Write the objection into `docs/04-decisions.md` as a proposal — what the brief says, what you think is better, what it costs to change — and continue with the brief until a human decides. Four agents each quietly following their own judgement produces something worse than one imperfect plan followed consistently.

---

# PART 12 — THE FIRST THING TO DO

If you are the first agent to read this:

1. Create `AGENTS.md` at the repository root from the file supplied alongside this brief.
2. Create `docs/` and put this brief in it as `00-integration-brief.md`, with the other planning documents beside it.
3. Create `STATUS.md` at the root with the job board from Part 5, all unclaimed.
4. Commit those three on a branch called `docs/agent-contract`, and tell the owner it is ready to merge.
5. Then take **Job 1**.

Nothing in steps 1 to 4 is deployed, so that branch cannot affect the live site.

---

*This brief supersedes the build sequencing in every earlier planning document. Where an older document conflicts with it, this one is correct — it was written after reading the repository.*
