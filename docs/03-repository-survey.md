# THE FOOD ECONOMIST — REPOSITORY SURVEY AND REVISED PLAN

**Repository:** `kfrem/the-food-economist-live-sites`
**Surveyed:** 25 August 2026
**Purpose:** establish what exists before anything is written, and correct the earlier plans against reality.

---

## 1. WHAT IS ACTUALLY THERE

Three sites in one repository, each deployed to its own document root on Hostinger.

| Folder | Domain | State |
|---|---|---|
| `root/` | thefoodeconomist.co.uk | Umbrella site. 10 files. Thin. |
| `myprofit/` | myprofit.thefoodeconomist.co.uk | **The restaurant desk. Substantially complete and trading.** |
| `epr/` | epr.thefoodeconomist.co.uk | The packaging desk. Built, large landing page. |

48 files. 20 HTML, 10 PHP, plus SQL schema, sitemaps, robots and assets.

### 1.1 The architecture is PHP and MySQL, not static

This matters more than anything else in the survey, because my earlier recommendation was wrong.

Each desk carries a working server-side API:

```
myprofit/api/
  booking.php        285 lines — slot booking, ICS calendar invites,
                     cancellation tokens, email notification
  automation.php     365 lines — the automation layer
  save-lead.php      lead capture
  save-intake.php    intake capture
  schema.sql         MySQL schema
  config.php         credential-free bridge to server-side config
  booking-data/      runtime data, git-ignored except its .htaccess
```

`epr/` mirrors this exactly.

**Consequence: drop Supabase.** I recommended a static site talking to Supabase because I assumed there was no back end. There is one, it works, it is deployed, and it holds live bookings. Replacing it would be destruction dressed as progress. Everything new should be written as PHP against the existing pattern, or as pure client-side JavaScript where no server is needed.

### 1.2 Deployment is GitHub Actions over SSH

`.github/workflows/deploy-hostinger.yml`. A push to `main` tars the three folders and unpacks them over SSH into the Hostinger document roots. It deliberately excludes `api/config.php` and `api/booking-data/`, and it verifies the remote directories exist before uploading.

This is properly done. Two practical implications:

- **A merge to `main` is a live deployment.** Branch protection is not optional here.
- New files go live automatically once merged. No manual upload step.

### 1.3 Security check — clean

I scanned the whole repository for credentials, since it was briefly public.

**Nothing exposed.** The committed `config.php` files are three-line bridges that `require` the real configuration from outside the web root. Real credentials never enter git. `.gitignore` is carefully written and handles the runtime data directory correctly.

The workflow does contain the Hostinger host address, port and username in plain text. That is not a credential — the SSH private key is held as a repository secret — but it is worth knowing they are visible to anyone who reads the workflow.

**No action needed.** Codex handled this correctly.

---

## 2. WHAT THE RESTAURANT DESK ALREADY DOES

This is the part I most underestimated. MyProfit is not an idea. It is trading.

**Free — the Restaurant Profit Check** (`verdict.html`, 686 lines)

- Quick check: sales, food, payroll, other costs, entered at whatever frequency the owner actually pays them
- Detailed mode: individual cost lines plus custom lines the owner adds himself
- **Planning scenarios**: supplier, payroll and energy percentage movements, with payroll defaulting to the 2026 National Living Wage increase
- Banded verdict with interpretation, no email required for the result
- Optional lead capture *after* the result is given

**Paid — a two-step ladder, live on Stripe**

| Product | Price | Mechanism |
|---|---|---|
| 48-Hour Profit Triage Report | £95 + VAT | Written, 48 hours, from rough figures |
| Menu & Margin Diagnostic | £395 + VAT | Fixed fee, dish-level |

The £95 is **credited in full against the £395 within 30 days**. That is a well-designed ladder and it is better than the one I proposed.

**Also built and running**

- A prepared-call booking engine: time slots, ICS invites, cancellation links, email confirmations
- A partner programme: £50 per paid Diagnostic, or white-label under the partner's own letterhead at a trade rate
- A worked-example sample report
- Competitor framing on the landing page — costing software at ~£129/month, on-site consultants at £75–£350/hour, MyProfit between them
- VAT gross display logic on prices

---

## 3. WHERE MY EARLIER PLANS WERE WRONG

Stated plainly, so the documents can be corrected rather than quietly contradicted.

| I recommended | Reality | Correction |
|---|---|---|
| Static site + Supabase back end | PHP + MySQL, working, deployed | **Drop Supabase.** Build on the existing PHP pattern. |
| Diagnostic ladder at £149 / £199 / £349 | £95 + VAT and £395 + VAT, live, with a credit mechanism | **Adopt the live prices.** They are better structured. |
| Build a referral channel | Already built — £50 per Diagnostic, white-label option | Nothing to build. Recruit partners. |
| Public tools must capture nothing | Deliberate lead and booking capture, with a privacy notice | His choice, and defensible. Keep the *result* free and unconditional, which it already is. |
| A new "Restaurant Vitals" brand and domain | MyProfit exists, is branded, ranks under the parent domain | **Vitals becomes the monthly tier of MyProfit.** No new brand, no new domain. |

The commercial reasoning in Business Plan v4 survives — the affordability arithmetic, the price ceiling by turnover band, the food economics positioning, the FSA prospect source. The technical plan does not.

---

## 4. THE REAL GAPS

Four things are genuinely missing, and only one of them is what I originally proposed.

### Gap 1 — There is no recurring revenue at all

Every product on the site is one-off. Triage, Diagnostic, prepared call, EPR reports. Nothing bills monthly.

This is where the monthly service belongs: **as a third step in the existing MyProfit ladder**, not as a separate business.

```
Free Profit Check  →  £95 Triage  →  £395 Diagnostic  →  Monthly tier
     (built)           (built)         (built)            (missing)
```

Priced by turnover band per the v4 arithmetic — roughly £99, £249 and £449 + VAT — and sold to Diagnostic buyers at the point of delivery, when the value has just been demonstrated. That is the warmest audience that will ever exist for it.

### Gap 2 — One calculator, where there should be seven

The Profit Check is good. It is also the only tool, which means the site captures only the owners who already know they have a margin problem.

Missing, in order of search demand and commercial pull:

1. **Delivery Commission Calculator** — the strongest search intent in the sector, almost no serious UK competition, and it feeds the £95 Triage directly
2. **Menu Dish GP Calculator** — feeds the £395 Diagnostic directly; after three dishes, prompt for the full menu
3. Food Cost Percentage Calculator
4. Labour Cost Calculator
5. Break-Even Covers Calculator
6. Food Waste Calculator

Each is a static page on `myprofit/`. No PHP, no database, no dependency. Each ends at the Triage.

### Gap 3 — The EPR desk sends its traffic to competitors

`root/resources.html` links out to the **Beyondly** and **EP Group** EPR calculators.

Those are the two tools an EPR prospect will use, and you are handing them the visitor. Given EPR reports run £295–£1,250 against £95–£395 on the restaurant side, **your own EPR fee estimator may be the single most valuable page on the whole estate.** It is the same build pattern as the restaurant calculators.

### Gap 4 — Nothing exists to produce the reports efficiently

There is no system for actually writing a £95 Triage or a £395 Diagnostic. That work is presumably manual today.

At low volume that is correct and I would not change it. But it is the ceiling: at eight Triages and four Diagnostics a month it becomes the whole week. When volume justifies it, the answer is a private report-builder — a form that takes the intake data and produces the report skeleton, leaving the judgement to you. Not before then.

---

## 5. SMALLER FINDINGS WORTH FIXING

**`root/sitemap.xml` lists six URLs** and omits the desks entirely. `epr` and `myprofit` have their own sitemaps, which is correct, but the umbrella site should still reference them. Submit all three in Search Console.

**The root site is thin** — a 185-line index and five supporting pages. It carries the brand and the biography, but almost no content that could rank. This is where your food economics writing belongs.

**Google Fonts is the only external dependency** across the estate. Harmless on your own hosting, but it is a render-blocking request on every page. Worth considering a system font stack later.

**Cache-busting is manual** — `styles.css?v=5`. Fine, but remember to increment it when CSS changes, or the release check in your own README will pass on a stale file.

---

## 6. WHAT TO BUILD, IN ORDER

Each item is a branch, merged by pull request, deployed on merge.

| # | Build | Where | Why first |
|---|---|---|---|
| 1 | Delivery Commission Calculator | `myprofit/delivery-commission.html` | Highest search intent, no real UK competitor, feeds the £95 Triage |
| 2 | Menu Dish GP Calculator | `myprofit/menu-gp.html` | Feeds the £395 Diagnostic, the higher-value product |
| 3 | EPR Fee Estimator | `epr/fee-estimator.html` | Stops the traffic leak to Beyondly and EP Group |
| 4 | Sitemap and internal linking pass | all three | Cheap, immediate, compounding |
| 5 | Four remaining restaurant calculators | `myprofit/` | Breadth of search coverage |
| 6 | The monthly tier page and billing | `myprofit/` | The missing revenue line — but sell it to Diagnostic buyers first, before building the page |
| 7 | Report builder | private area | Only when volume demands it |

Items 1 to 4 are perhaps a week of work and every one of them adds either traffic or conversion to something already trading.

---

## 7. THE CONVENTIONS ANY NEW WORK MUST FOLLOW

Taken from what Codex has already established, so the estate stays coherent:

- **Single-file pages.** Each page carries its own inline `<style>` and `<script>`. No shared bundle on the desks.
- **CSS custom properties** for colour, already defined per desk. Reuse the existing tokens.
- **Fonts:** Playfair Display for display, Inter for body, via Google Fonts.
- **British English, GBP, `+ VAT` shown net with a gross helper.**
- **Mobile-first**, with `inputmode="decimal"` on numeric fields and `clamp()` typography.
- **Honeypot fields** on every form — the existing forms use a hidden `website` input. Match it.
- **The result is always free and unconditional.** Lead capture comes after the answer, never before it.
- **Every page ends at a commissioned product**, not at a generic contact form.
- Files land in the desk folder that owns them; the deploy workflow handles the rest.

---

## 8. RECOMMENDED IMMEDIATE ACTIONS

1. **Turn on branch protection for `main`.** A merge deploys to production. With four agents contributing, this is the single most important control.
2. **Add `AGENTS.md` to the repository root** — adapted to this structure, not the greenfield scaffold I sent earlier. It should state the PHP-and-Hostinger architecture, the single-file page convention, the deploy-on-merge behaviour, and the "never commit `config.php` contents" rule.
3. **Revise the three plan documents** so they describe this estate rather than an imaginary one.
4. **Build the Delivery Commission Calculator** as the first piece of work.

---

*Survey based on a full read of the repository at commit HEAD, 25 August 2026. No files were modified and nothing was pushed.*
