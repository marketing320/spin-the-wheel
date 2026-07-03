# 🎡 Spin The Wheel

An admin-configurable **Spin The Wheel** prize game built on **Laravel 13 + Livewire 4**.
A player registers with their email, verifies an OTP, completes a dynamic form, and spins a
premium 3D wheel on their phone — while the exact same spin is mirrored in real time on a public
`/live-view` event screen.

Built for correctness first: prizes are chosen **server-side**, only **one player can spin at a
time globally**, and eligibility/geofence are validated on the backend.

---

## ✨ Features

- **Email OTP registration** — hashed codes, configurable expiry, resend throttling, attempt lockout.
- **Dynamic registration form** — admin-built fields (text, email, phone, number, select, radio,
  checkbox, date, consent) with per-field validation.
- **Server-side prize selection** — strict-percentage or weighted modes, inventory reservation,
  out-of-stock/disabled exclusion, cryptographically secure randomness.
- **Global one-at-a-time spin lock** — enforced by a unique DB guard + row locking, with a
  failsafe timeout that releases stuck spins.
- **Play-frequency rules** — once per campaign / per day, every X hours, max per campaign/day,
  keyed on the player's email identity.
- **Geofence** — server-side Haversine validation with private audit logs.
- **Realtime sync** — Laravel Reverb broadcasting drives identical animation on the phone and the
  live-view screen from one server payload; late-joining screens resync to the elapsed position.
- **3D animation** — Three.js wheel (with a 2D canvas fallback) + `canvas-confetti` celebrations
  scaled by prize rarity.
- **Full admin panel** — dashboard, campaigns, prizes, wheel design, play rules, form builder,
  geofence, live-view settings, spin history (CSV export), players, and global settings.

---

## 🧰 Requirements

- PHP 8.3+ (tested on 8.5)
- Composer 2
- Node.js 20+ and npm
- MySQL 8
- A mail account for sending OTP emails (SMTP)

---

## 🚀 Setup

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your database + mail credentials. Reverb credentials are pre-scaffolded;
generate fresh ones any time with `php artisan reverb:install`.

```bash
# 3. Create the database (MySQL), then migrate + seed demo data
php artisan migrate --seed

# 4. Link storage (for prize image uploads)
php artisan storage:link

# 5. Build the frontend
npm run build      # or: npm run dev  (during development)
```

### Running

You need up to three processes:

```bash
php artisan serve          # the web app          -> http://localhost:8000
php artisan reverb:start   # the websocket server -> realtime sync
php artisan queue:work     # queue worker         -> broadcasts + failsafe spin completion
```

> Realtime + the failsafe completion job need the queue worker and Reverb running. The player page
> still works without them (it animates from the HTTP response), and `/live-view` falls back to
> polling if Reverb is offline.

### Deploying behind HTTPS (reverse proxy)

When served over HTTPS through a proxy (nginx / Caddy / Cloudflare), set on the server:

```dotenv
APP_URL=https://your-domain.com     # forces https URLs → no "mixed content" / Livewire errors
```

Then `php artisan config:clear` (or re-run `config:cache`). The app already trusts proxy headers
(`X-Forwarded-Proto`) so it detects HTTPS automatically.

**WebSockets:** the browser connects to `wss://your-domain.com/app/...`; your proxy must forward
the `/app` path (with WebSocket upgrade) to the Reverb server on `127.0.0.1:8080`. Example nginx:

```nginx
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

The bundled `echo.js` auto-detects the domain + `wss:443` when the page is HTTPS, so **you do not
need to rebuild** just to change the websocket host. (You may still set `VITE_REVERB_HOST` /
`VITE_REVERB_PORT` / `VITE_REVERB_SCHEME` explicitly and rebuild if you run Reverb on a separate
host/port.) Keep `php artisan reverb:start` and `php artisan queue:work` running.

---

## 🔑 Demo accounts & routes

After seeding:

- **Admin panel:** <http://localhost:8000/admin> — `admin@example.com` / `password`
- **Player game:** <http://localhost:8000/>
- **Live screen:** <http://localhost:8000/live-view>

The seeder creates an active *"Grand Launch Giveaway"* campaign with five prizes (Common →
Legendary), a demo registration form, a once-per-day play rule, and a disabled geofence.

### Player journey

`/` → `/register` → `/verify-otp` → `/player/form` → `/spin` → `/result/{spin}`

> **Tip:** with `MAIL_MAILER=log` the OTP code is written to `storage/logs/laravel.log` instead of
> being emailed — handy for local testing.

---

## 🧱 Architecture

**Services** (`app/Services`) hold the business logic; controllers/Livewire components stay thin:

| Service | Responsibility |
|---|---|
| `OtpService` | Issue/verify hashed OTPs, expiry, throttling, lockout |
| `PrizeSelectionService` | Server-side weighted/strict selection + inventory reservation |
| `SpinEligibilityService` | Verification, form, campaign window & play-frequency checks |
| `SpinLockService` | Global single-spin guard + stale-lock expiry |
| `GeofenceService` | Haversine distance validation + audit logging |
| `WheelAnimationService` | Deterministic segment layout + final-angle math |
| `SpinService` | Orchestrates the full spin lifecycle + broadcasting |

**Realtime** — `SpinStarted`, `SpinCompleted`, `SpinExpired` broadcast on the public `spin-stage`
channel. The frontend modules live in `resources/js/spin/` (`wheel-scene`, `spin-controller`,
`confetti-controller`, `live-sync`, `player-sync`) and are driven by one shared server payload so
both screens animate identically.

**Runtime settings** — most OTP/spin/branding/live-view options are editable in the admin panel and
persisted in `app_settings` (see `App\Support\Settings`), falling back to `config/spin.php`.

---

## ✅ Tests

```bash
php artisan test
```

Covers the core rules: no spin without verification/form, play-rule blocking, geofence blocking,
one-at-a-time locking, server-side & out-of-stock prize selection, OTP expiry/attempt limits,
lock expiry, and the live-view active-spin fetch.

---

## 🔒 Security highlights

- OTPs hashed at rest; never returned to the client.
- CSRF protection on all forms; rate limiting on OTP request/verify, admin login, and spin start.
- All eligibility, geofence, and prize decisions happen server-side.
- Admin routes protected by auth + an `is_admin` gate; players use an isolated `player` guard.
- Uploaded prize images are validated (type + size).
