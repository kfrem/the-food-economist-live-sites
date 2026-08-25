# THE ENGINE, RESTORED
## Why the live data platform is the business, and the website is its front door

**For:** Onyameadamfo
**Date:** 25 August 2026
**Supersedes:** the build sequencing in the Repository Survey. The survey's technical findings stand; its priorities were wrong.

---

## 1. THE ERROR I MADE

I surveyed your repository, found a well-built site that is already trading, and let that define the ceiling of the plan. I listed the live data platform as "Gap 1", then placed it sixth on a build list of seven. I treated the ops console as a separate item to be built "when volume demands it."

Three mistakes in that.

**First, I treated two halves of one thing as two things.** The client-facing live dashboard and your own data-entry console are not separate builds. They are the same engine seen from two sides. The system that lets you produce a report in twenty minutes instead of three hours is the same system that shows the client what changed this week. I split them and then deprioritised both.

**Second, I let what exists define what should exist.** Your site is good, so I optimised around it. But a well-built front door does not tell you what the building should be.

**Third, and worst: I confused the thing that pays this month with the thing that is worth something.** One-off reports are you selling hours. They stop when you stop. They cannot be sold, cannot be delegated far, and get no better with time. The engine is the opposite on every count.

You were right to push back.

---

## 2. WHAT YOU HAVE VERSUS WHAT YOU DESCRIBED

These are different products and both are legitimate. The confusion has been calling them one thing.

| | **What is live now** | **What you described** |
|---|---|---|
| Data source | The owner types four numbers | His actual invoices, tills and statements |
| Frequency | Once, when he visits | Continuous |
| What it knows | What he told it, this minute | What his business has actually done for months |
| What it can say | "Your margin sits here" | "Your haddock moved 14% since 14 July. Act this week." |
| Improves over time | No | Yes, and compoundingly |
| If he stops paying | Nothing changes | He loses the record |
| Value to you | Generates leads | **Is the asset** |

The Profit Check is a **marketing engine**. It is very good at its job. Your other developers are right about that.

What you described is a **data engine**. It does not exist yet, and it is the business.

---

## 3. THE THING THAT MAKES IT A GAME-CHANGER

Not the wheel. Not the daily message. Those are presentation.

**It is that no individual restaurant can see the market, and you will be able to.**

An owner in Leyton knows what he pays for haddock. He does not know what the shop two miles away pays, or what the median across fourteen London fish-and-chip shops is, or that his supplier raised him nine per cent in March while holding others flat. He has no way to know. There is no source. His accountant cannot tell him. His POS cannot tell him. No software on the market tells him, because none of them hold other people's invoices.

Once invoices flow through your system from twenty independents, you can write this sentence:

> **"You pay £9.60 a kilo for haddock. The median across fourteen comparable London sites is £8.90. You are paying eight per cent over the market, and it has been true since March. That is £1,340 a year on this one line."**

That sentence is worth £249 a month on its own. It is the sentence a food economist is uniquely positioned to write and nobody else in the market can produce. It is also, precisely, what "communicating with historical data is not helping them see their way forward" was pointing at.

And it has the property every good business wants: **it gets better with every client, and it cannot be copied by anyone who does not have the data.** A software company can clone your dashboard in a fortnight. It cannot clone four hundred London invoice histories.

That is the engine. Everything else is a way of getting invoices into it or insight out of it.

---

## 4. WHY THE VERSION 3 PLAN STALLED — AND WHY THAT DOES NOT CONDEMN THE VISION

Version 3 said: the owner photographs invoices and sends them on WhatsApp; you type everything in. That is what caps the business at five to seven clients and it is what I have been reacting against for three days.

But **the typing was never the vision. It was one implementation of it, and the weakest one available.** The vision requires data to arrive. It does not require you to key it.

Four sources, none of which need the client to do anything daily:

**1. Supplier invoices by email.** Most UK catering suppliers — Brakes, Bidfood, regional fish and meat merchants, drinks wholesalers — email PDF invoices as standard. Give each client an address on your domain, ask them once to add it to their supplier account as a copy recipient, and the invoices arrive forever without anyone lifting a phone. This is the single highest-leverage mechanism in the entire business.

**2. Delivery platform remittances.** Deliveroo, Uber Eats and Just Eat send weekly remittance statements with commission, refunds and adjustments broken out. They are structured, they are predictable, and they land by email. Parsing them gives you the delivery contribution picture with no client effort at all.

**3. Till exports.** Most EPOS systems email a daily Z-report or can export CSV. One setup conversation, then automatic.

**4. Bank data.** Later, and only if it earns its place. Open Banking read-only access through a regulated provider. Not year one.

The client's total ongoing effort under this model is **zero**. Not "twenty minutes a day", not "photograph your invoices" — zero. That is not a smaller version of your vision. It is a stronger one, and it removes the objection that killed the original plan.

Your own effort is not typing. It is the twenty minutes of judgement per client per week that no machine can do, which is exactly the part clients are paying for.

---

## 5. THE ARCHITECTURE — AN EXTENSION, NOT A REBUILD

This is the good news the survey did produce. You already have most of the skeleton.

```
ALREADY BUILT AND RUNNING                    TO BE ADDED
─────────────────────────                    ───────────
PHP + MySQL on Hostinger          ──────►    client accounts, sessions
api/save-lead.php                            invoice ingestion mailbox
api/save-intake.php               ──────►    line-item parser
api/booking.php (slots, ICS,                 price history table
  tokens, email)                             benchmark engine
api/automation.php                ──────►    client dashboard
api/schema.sql                               entry/review console
GitHub Actions → SSH → Hostinger  ──────►    same pipeline, no change
Stripe payment links              ──────►    Stripe subscriptions
```

You are not starting a platform. You are adding tables and pages to a working application that already handles authentication-adjacent flows, stores data, sends email, generates calendar invites and deploys itself. Codex built a better foundation than either of us gave it credit for.

The core of the new schema is small:

```
clients            who they are, segment, turnover band, tier
suppliers          per client, plus a global reference list
items              the catalogue — "haddock, fresh, 5kg box"
invoice_lines      date, client, supplier, item, qty, unit price, total
price_points       item, date, unit price, segment, region   ← THE ASSET
benchmarks         median, quartiles, movement, by item and segment
reviews            periodic readings per client
notes              what you sent them and when
```

The table that matters is `price_points`. Everything else is plumbing. **That one table, populated across enough clients, is the whole competitive position.**

---

## 6. THE COLD START — AND THE ANSWER THAT COSTS NOTHING

The honest weakness: a benchmark needs perhaps ten to fifteen sites per segment before it says anything defensible. Below that, "the median across three shops" is not a market rate and you should not present it as one.

So how do you get the first hundred invoices before you have any subscribers?

**You already are getting them, and you are throwing them away.**

Every £95 Triage and every £395 Diagnostic you deliver involves reading a client's real invoices. That data is passing through your hands right now and leaving no trace. From this week, **every paid report should deposit its line-level prices into the price table before the report is written.**

You do nothing extra commercially. You sell the same reports at the same prices to the same people. But six months of Triage work has quietly built the dataset that makes the subscription product possible — and makes each report better than the last, because the fifteenth Diagnostic is benchmarked against the previous fourteen.

Say so in the terms: data is aggregated across a minimum number of sites, never attributed to any individual business, and used to improve the benchmarks every client sees. That is honest, it is a selling point, and it is what every serious benchmarking service does.

This is the single most valuable thing in this document. It costs nothing, starts immediately, and it is the difference between launching the engine in month nine with real data and launching it in month nine with an empty table.

---

## 7. THE REVISED SHAPE

The website is not the business. It is the funnel that feeds the engine.

```
   FREE PROFIT CHECK          the hook — already built, working
            │
            ▼
   £95 TRIAGE                 proof, cash, AND the first invoices
            │                 into the price table
            ▼
   £395 DIAGNOSTIC            deeper proof, more data, credited from the £95
            │
            ▼
   ══════════════════════════════════════════════
   THE LIVE ENGINE            the business
   ══════════════════════════════════════════════
   invoices arrive by email, automatically, forever
   prices tracked at line level, week by week
   benchmarked against every other client
   a weekly reading and three specific actions
   £99 / £249 / £449 + VAT by turnover band
            │
            ▼
   PROCUREMENT SAVINGS SHARE  25% of verified first-year saving
                              — only possible because the data exists
```

Each step pays for itself and feeds the next. The reports are not a distraction from the engine; they are how it is funded and populated.

---

## 8. SEQUENCING, WITH THE ENGINE AT THE CENTRE

| Phase | What | Why here |
|---|---|---|
| **0. This week** | Start capturing line-level prices from every paid report into a simple structured store — a spreadsheet is enough to begin | Solves cold start. Costs nothing. Cannot be done retrospectively. |
| **1. Weeks 1–3** | The ingestion spine: client accounts, an invoice mailbox per client, the parser, `invoice_lines` and `price_points` | This is the engine. Everything depends on it. |
| **2. Weeks 3–5** | Your review console — the screen where you confirm parsed lines and write the reading | The half that makes reports fast. Build it with the engine, not later. |
| **3. Weeks 5–6** | Client dashboard and the weekly reading | The half the client sees. |
| **4. Week 7** | The benchmark engine — medians, quartiles, movement, by segment | Switch on once the data supports it, not before. |
| **5. Ongoing** | Delivery Commission Calculator, Menu GP Calculator, EPR estimator | Feed the funnel. Genuinely valuable, genuinely secondary. |

Note what moved. In the survey those calculators were items one to three. They are now item five. They bring traffic, and traffic is not the constraint — the constraint is that there is no product at the end worth compounding.

---

## 9. WHAT THIS IS WORTH, AND WHY IT IS DEFENSIBLE

A consultancy selling written reports is worth roughly what its founder can bill. It does not sell, it does not scale past his hours, and it stops when he does.

An operating data platform for UK independent food businesses, holding the only line-level price history of its kind, with recurring revenue and a savings share on top, is a different class of thing entirely. It has a moat that widens on its own, it improves the consultancy that feeds it, and it is an asset rather than a job.

You had the right instinct. What was missing was not ambition — it was the mechanism to get data in without either of you doing the typing. That mechanism is a mailbox and a parser, and it is a fortnight's work on the stack you already run.

---

## 10. WHAT I WOULD DO NEXT

1. **This week, before any code:** start depositing line prices from every paid report into a structured store. Even a spreadsheet with columns for date, supplier, item, unit, unit price, segment and postcode district. That file becomes the seed of `price_points`.
2. **Add the aggregation clause** to your terms of engagement so the data can be used properly from the start.
3. **Then build the ingestion spine** — mailbox, parser, tables — on the existing PHP and MySQL.
4. The calculators come after, and they will convert better once there is a live product waiting behind them.

Say the word and I will specify the ingestion spine properly: the mailbox routing, the parser design, the full schema, and the console — written against the conventions Codex has already established in your repository, so it merges rather than fights.
