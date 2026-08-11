# The Food Economist Live Sites

This is the release source for the live public sites. It is intentionally separate from working notes, legacy portals, test assets, and Hostinger runtime configuration.

## Release map

| Repository folder | Public domain | Hostinger document root |
| --- | --- | --- |
| `epr/` | `epr.thefoodeconomist.co.uk` | EPR subdomain root |
| `myprofit/` | `myprofit.thefoodeconomist.co.uk` | MyProfit subdomain root |

## Release rules

1. `main` is production only after a preview check.
2. Server-only `api/config.php` is never committed or overwritten by a release.
3. `api/booking-data/` is runtime data and is excluded, except for its `.htaccess` protection file.
4. The Hostinger File Manager is emergency recovery only. Normal releases come from GitHub.
5. Every release must be checked on its public URL with a cache-busting query parameter before it is marked live.
