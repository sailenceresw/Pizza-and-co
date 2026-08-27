# Pizza & Co Thornaby Takeaway Website

A full, runnable takeaway-ordering website built with **HTML, CSS, JavaScript
and PHP** (PDO + SQLite). Coursework project structured explicitly around the
**five planes of User Experience** (Garrett).

> **Inspiration & attribution.** This project is *inspired by* the real
> **Pizza & Co Thornaby** business as a design brief — same business type,
> same audience, same town (Thornaby, TS17). It is **not** a commercial clone:
> every line of code, all copy, and all artwork (SVG food illustrations + CSS
> animation) in this repository is original work produced for assessment. No
> assets were lifted from the real site.

---

## Quick start (it really runs)

No build step, no MySQL server, no Composer. You only need PHP 8.1+ with the
`pdo_sqlite` extension (bundled with PHP by default).

```bash
php -S localhost:8000
```

Then open <http://localhost:8000>.

The SQLite database (`data/pizzaco.sqlite`) is **created and seeded
automatically on first request** — 11 categories, 30+ products, sizes,
toppings, crusts, deals and demo accounts. To reset everything, delete that
file and reload.

### Demo accounts

| Role     | Email                      | Password   |
|----------|----------------------------|------------|
| Customer | `sam@example.test`         | `password` |
| Admin    | `admin@pizzaandco.test`    | `admin123` |

The admin dashboard is at `/admin.php` (link appears in the nav when signed in
as an admin).

---

## The five planes of UX

### 1. Strategy
**Business goals:** drive direct orders to avoid third-party platform fees
(“£0 platform fees”); beat Just Eat / Uber Eats on speed and price; build
repeat custom via accounts and deals.
**User needs:** browse a big menu fast, filter by diet, check the delivery
zone, order for delivery/collection with minimum friction, track and reorder.
**Success metrics:** completed checkouts, average order value, account
signups, repeat-order rate — all surfaced live on the admin dashboard.

### 2. Scope
User auth (register / login / password reset), session **+ DB-persistent
cart**, the JS **Pizza Builder**, diet & spice **menu filtering**, **postcode
delivery checker**, **checkout** (delivery/collection toggle, time slots,
demo-stubbed payment), **order history & reorder**, **admin order pipeline**,
contact form, newsletter signup, and a **live kitchen open/closed** status
computed from opening hours vs. current time.

### 3. Structure
Nine page types, one PHP product template driving every dish from the DB:
Home · Menu · Product detail · Cart · Checkout · Order confirmation ·
My Account · About · Contact · Admin (+ auth & accessibility pages).

### 4. Skeleton
Sticky header with logo, primary nav, live cart badge, account icon, theme
toggle and an open-status pill; an order-mode **modal** (delivery/collection +
postcode check); menu page with sidebar category nav + filters; product page
with live-priced options; sticky checkout/cart summary; rich footer with hours,
map, newsletter and validator badges.

### 5. Surface
Deep-tomato accent, off-white cream background, charcoal text, mustard “deal”
highlights. **Fraunces** display + **Inter** body. Original SVG food
illustrations with a self-developed **CSS steam animation**, a spinning hero
pizza, and a complete **dark mode**.

---

## Assessment-criteria mapping

| Criterion | Where it lives |
|---|---|
| **Extensive functionality** | auth, cart, builder, filters, postcode, checkout, orders, admin pipeline, reviews, newsletter, contact |
| **JavaScript interactivity** | `assets/js/`: pizza builder, AJAX cart with ARIA announcements, live menu filter/search, postcode checker, dark mode, modal, lightbox, live price |
| **PHP** | PDO/SQLite layer with auto-migrate + seed, sessions, CSRF, password hashing, order transactions, admin CRUD (price/visibility), contact mailer stub |
| **Database** | `users, categories, products, product_sizes, toppings, crusts, deals, addresses, cart_items, orders, order_items, reviews, newsletter, messages` |
| **Multimedia** | original SVG food art, CSS steam/dough/spin animations, embedded map |
| **Accessibility** | semantic HTML5, skip link, keyboard-operable builder (`role`/`tabindex`/`aria-pressed`), `aria-live` cart updates, focus styles, `prefers-reduced-motion`, dark mode — see `/accessibility.php` |
| **Validation** | validator/WAVE badges in the footer; see *Validation & testing* below |

---

## Project structure

```
config/      config.php (constants, sessions, CSRF) · db.php (schema + seed)
includes/    functions · auth · media (SVG art) · header · footer
api/         cart.php · postcode.php · newsletter.php  (JSON endpoints)
assets/      css/style.css · js/{main,cart,filters,builder}.js
data/        pizzaco.sqlite (auto-generated; git-ignored)
*.php        the page set (index, menu, product, cart, checkout, …)
```

## Security notes

Prepared statements everywhere (no string-built SQL), `password_hash` /
`password_verify`, per-session CSRF tokens on every state-changing form,
output escaped via `htmlspecialchars`, session cookie `HttpOnly` + `SameSite`,
and `session_regenerate_id()` on login/logout.

## Demo limitations (by design, for coursework)

- **Payment is stubbed** — the “Pay by card” option is clearly marked `DEMO`
  and never processes a real card.
- **Email is stubbed** — contact messages and password-reset links are stored
  / shown in-app instead of sent, since there is no mail server.

## Validation & testing

Pages are written to pass the [W3C HTML5](https://validator.w3.org/) and
[CSS3](https://jigsaw.w3.org/css-validator/) validators and were reviewed with
[WAVE](https://wave.webaim.org/). Add your validator/WAVE screenshots to a
`docs/` folder and link them here for the final submission.
