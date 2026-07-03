# CLAUDE.md — Fresh Laravel 13 Spin The Wheel Web App

You are building a fresh Laravel web application for an admin-configurable **Spin The Wheel prize game**.

The app must be production-ready, secure, mobile-friendly, and visually impressive. The main experience is a player spinning a wheel on their phone while the same spin is synchronised in real time to a public live display page at `{{url}}/live-view`.

---

## 1. Tech Stack

Use the following stack:

- Laravel 13
- PHP compatible with Laravel 13
- MySQL 8
- Node.js
- Vite
- Livewire
- Alpine.js
- Three.js
- Confetti.js
- Laravel queues / jobs where useful
- Laravel broadcasting / WebSockets / Reverb-compatible realtime layer
- Laravel Mail / notification system for email OTP
- Laravel validation, policies, middleware, rate limiting, and CSRF protection

Build this as a clean Laravel project, not a prototype.

---

## 2. Product Goal

Create a web-based spin-the-wheel prize game where:

1. Players register with email.
2. Players verify their email using an email OTP.
3. After OTP verification, players must complete a configurable registration form.
4. Only eligible players can start a spin.
5. Only **one player can spin the wheel at a time globally**.
6. When a player spins on their phone, the exact same spin timing, selected prize, wheel rotation, animation, and celebration effects must be shown on `{{url}}/live-view`.
7. Admin can configure almost everything from the admin panel.
8. Admin can configure prizes, win percentage, play frequency, geofence, registration form fields, animation settings, and campaign rules.

---

## 3. Core Pages

### Public / Player Pages

Create these pages:

- `/`
  - Landing page with campaign information.
  - Call-to-action to register or continue.

- `/register`
  - Player email registration page.
  - Collect email.
  - Send OTP to email.

- `/verify-otp`
  - OTP verification page.
  - OTP must expire after a configurable duration.
  - OTP resend must be rate-limited.

- `/player/form`
  - Dynamic registration form shown only after successful OTP verification.
  - Form fields must be configurable by admin.
  - Player must complete this before spinning.

- `/spin`
  - Main player spin page.
  - Mobile-first design.
  - Shows eligibility status.
  - Shows countdown / waiting state if another player is spinning.
  - Allows spin only when player is eligible and no one else is currently spinning.

- `/result/{spin}`
  - Shows player result after spin.
  - Shows prize name, prize rarity, redemption instruction, and any configured message.

### Live Display Page

- `/live-view`
  - Public or optionally protected display page for event screen / projector / TV.
  - Must mirror the active player spin in real time.
  - When player spins from phone, this page must show:
    - Same wheel segments.
    - Same target prize.
    - Same spin start time.
    - Same spin duration.
    - Same final angle.
    - Same celebration effect.
  - Should show idle screen when nobody is spinning.
  - Should show current player display name or masked email, based on admin configuration.
  - Must not expose sensitive player data.

### Admin Pages

Create an admin dashboard with authentication and authorization.

Admin pages required:

- `/admin`
  - Dashboard overview.
  - Total registered players.
  - Total spins.
  - Total wins by prize.
  - Current active spin status.

- `/admin/campaigns`
  - Create and manage campaigns.
  - Campaign name, status, start date, end date.
  - Active campaign control.

- `/admin/prizes`
  - Add, edit, disable, delete prizes.
  - Configure prize name, description, image, rarity, inventory quantity, win percentage, display color, confetti level, redemption message.

- `/admin/wheel`
  - Configure wheel visual appearance.
  - Segment colors, label style, logo, background, animation duration, sound toggle, Three.js intensity.

- `/admin/play-rules`
  - Configure how often players can play based on email.
  - Example rules:
    - Once per campaign.
    - Once per day.
    - Once every X hours.
    - Maximum X spins per email.
    - Custom cooldown period.

- `/admin/forms`
  - Build the player registration form.
  - Supported field types:
    - Text
    - Email
    - Phone
    - Number
    - Select
    - Radio
    - Checkbox
    - Date
    - Consent checkbox
  - Admin can set field label, placeholder, required status, options, sort order, and validation rules.

- `/admin/geofence`
  - Configure geofence rule.
  - Admin can enable / disable geofence.
  - Configure latitude, longitude, radius in meters.
  - Optional allowed branch / event location name.
  - Players outside allowed radius cannot spin.
  - Admin can configure the blocked message.

- `/admin/live-view`
  - Configure live-view settings.
  - Show/hide player name.
  - Show masked email only.
  - Configure idle message.
  - Configure live screen branding.

- `/admin/spins`
  - Spin history table.
  - Search by email, prize, date, campaign.
  - Export CSV.

- `/admin/players`
  - Player list.
  - View player profile, form responses, email verification status, spin count, last spin time.

- `/admin/settings`
  - Global settings.
  - OTP expiry duration.
  - OTP resend cooldown.
  - App branding.
  - Terms and conditions.

---

## 4. Main Business Rules

### Email OTP Registration

Player flow:

1. Player enters email.
2. System sends OTP to email.
3. OTP is stored securely.
4. OTP must expire after configured minutes.
5. OTP resend is rate-limited.
6. Too many failed attempts should temporarily block verification.
7. After successful OTP verification, mark player email as verified.
8. Player proceeds to dynamic registration form.
9. Player can spin only after completing the form.

Do not allow unverified players to spin.

### Play Frequency Based on Email

Admin can configure play frequency per campaign.

The system must check eligibility based on email identity, not only session.

Supported rules:

- Once per campaign
- Once per day
- Once every X hours
- Max X times per campaign
- Max X times per day

When a player is not eligible, show:

- Reason they cannot spin.
- Next available spin time, when applicable.

### One Player Can Spin At A Time

Only one active spin can happen globally at any moment.

Required behaviour:

- When a player starts spinning, create an active spin session.
- Lock the spin system until the spin is completed or expired.
- Other players see waiting state.
- `live-view` mirrors the active spin.
- If the player closes the browser midway, the spin must still complete server-side.
- Add a failsafe timeout to release stuck spin locks.

Use a database-safe locking approach.

Recommended approach:

- Use a `spin_sessions` table.
- Use database transaction + row lock / atomic update.
- Only allow one `active` spin session at a time.
- Add `expires_at` to prevent permanent lock.
- Use server-generated spin result before animation starts.

Never determine the prize only on the frontend.

### Prize Selection / Win Percentage

Admin can configure win percentage for each active prize.

Rules:

- Prize selection must happen server-side.
- Frontend only receives the selected result after server has decided.
- The selected prize must be recorded in the database.
- Disabled prizes cannot be won.
- Out-of-stock prizes cannot be won.
- If inventory is enabled, decrement inventory safely.
- Percentages should be validated so the total active prize percentage is logical.

Support two modes:

1. Strict percentage mode
   - Active prize percentages must total 100%.

2. Weighted mode
   - Each prize has a weight.
   - System calculates probability based on total active weight.

Admin can choose the mode.

### Geofence

Admin can configure geofence for the campaign.

Player spin page should request browser location permission before spin if geofence is enabled.

Rules:

- Store configured latitude, longitude, and radius.
- Verify player location before allowing spin.
- Calculate distance server-side using Haversine formula.
- Do not rely only on frontend validation.
- Log geofence check result with approximate location, distance, and pass/fail status.
- Respect privacy: do not expose location logs publicly.

If the player denies location permission, show admin-configured blocked message.

---

## 5. Realtime Synchronisation

The phone spin page and `/live-view` must stay synchronised.

When player clicks spin:

1. Frontend requests `/api/spin/start` or Livewire action.
2. Backend verifies:
   - Player is logged in / verified.
   - Player form is completed.
   - Player is eligible based on play rules.
   - Geofence passes if enabled.
   - No other spin is active.
3. Backend selects prize server-side.
4. Backend calculates:
   - Spin session ID.
   - Prize ID.
   - Final wheel angle.
   - Spin duration.
   - Start timestamp.
   - End timestamp.
   - Animation seed.
5. Backend broadcasts `SpinStarted` event.
6. Player page and `/live-view` both animate using the same payload.
7. Backend marks spin as completed after the end time or via completion callback.
8. Backend broadcasts `SpinCompleted` event.
9. Confetti and prize reveal happen on both pages.

Important:

- Use server time as the source of truth.
- Include enough data in broadcast payload so both screens animate identically.
- If `/live-view` is opened midway through a spin, it must fetch the current active spin and continue from the correct elapsed time.

---

## 6. Animation Requirements

Make the spin animation extravagant, premium, and exciting.

Use:

- Three.js for 3D wheel / stage / lighting / glow / particles.
- Confetti.js for prize celebration.
- Alpine.js for UI state where useful.
- Livewire for server-backed interactions.

Animation style:

- Large 3D wheel with smooth acceleration and deceleration.
- Glowing wheel segments.
- Pointer indicator.
- Light burst before final result.
- Camera shake or zoom-in effect near stop.
- Prize reveal modal.
- Sound-ready architecture, but sound can be optional / configurable.
- Mobile-friendly performance.

Confetti levels based on prize rarity:

- Common prize
  - Light confetti.
  - Short duration.

- Uncommon prize
  - Medium confetti.
  - More particles.

- Rare prize
  - Strong confetti.
  - Longer duration.
  - Extra sparkle effect.

- Epic prize
  - Heavy confetti.
  - Screen burst.
  - Gold-style celebration.

- Legendary / Grand Prize
  - Maximum confetti.
  - Multiple waves.
  - Firework-style burst.
  - Dramatic prize reveal.

Admin must be able to configure confetti level for each prize.

---

## 7. Suggested Database Models

Create migrations, models, factories where useful, and seeders for demo data.

Suggested tables:

### users

Use Laravel default users table for admin users, or separate admins if preferred.

### players

Fields:

- id
- email
- email_verified_at
- display_name nullable
- otp_verified boolean
- form_completed_at nullable
- last_spin_at nullable
- created_at
- updated_at

### email_otps

Fields:

- id
- email
- otp_hash
- expires_at
- attempts
- resend_available_at
- verified_at nullable
- created_at
- updated_at

### campaigns

Fields:

- id
- name
- slug
- status
- starts_at nullable
- ends_at nullable
- active boolean
- settings json nullable
- created_at
- updated_at

### prizes

Fields:

- id
- campaign_id
- name
- description nullable
- image_path nullable
- rarity
- color nullable
- win_percentage decimal nullable
- weight integer nullable
- inventory_quantity nullable
- inventory_enabled boolean
- confetti_level
- redemption_message nullable
- is_active boolean
- sort_order integer
- created_at
- updated_at

### form_fields

Fields:

- id
- campaign_id
- label
- field_key
- field_type
- placeholder nullable
- options json nullable
- validation_rules json nullable
- is_required boolean
- sort_order integer
- is_active boolean
- created_at
- updated_at

### player_form_responses

Fields:

- id
- player_id
- campaign_id
- responses json
- created_at
- updated_at

### play_rules

Fields:

- id
- campaign_id
- rule_type
- cooldown_hours nullable
- max_spins_per_campaign nullable
- max_spins_per_day nullable
- is_active boolean
- settings json nullable
- created_at
- updated_at

### geofence_settings

Fields:

- id
- campaign_id
- enabled boolean
- location_name nullable
- latitude decimal
- longitude decimal
- radius_meters integer
- blocked_message nullable
- created_at
- updated_at

### geofence_logs

Fields:

- id
- player_id
- campaign_id
- latitude decimal nullable
- longitude decimal nullable
- distance_meters decimal nullable
- passed boolean
- reason nullable
- created_at
- updated_at

### spin_sessions

Fields:

- id
- campaign_id
- player_id
- prize_id nullable
- status enum: pending, active, completed, expired, failed
- started_at
- ends_at
- completed_at nullable
- expires_at
- spin_duration_ms integer
- final_angle decimal
- animation_seed string nullable
- request_ip nullable
- user_agent nullable
- metadata json nullable
- created_at
- updated_at

### spin_results

Fields:

- id
- spin_session_id
- campaign_id
- player_id
- prize_id
- result_payload json nullable
- created_at
- updated_at

### app_settings

Fields:

- id
- key
- value json nullable
- created_at
- updated_at

---

## 8. Admin Configuration Requirements

Admin should be able to configure without touching code:

- Campaign active status.
- Prize list.
- Prize image.
- Prize rarity.
- Prize win percentage / weight.
- Prize stock quantity.
- Confetti level per prize.
- Registration form fields.
- OTP expiry and resend cooldown.
- Player play frequency based on email.
- Geofence location and radius.
- Live-view display options.
- Wheel visual design.
- Idle screen text.
- Result messages.

Use validation and helpful error messages for all admin forms.

---

## 9. Security Requirements

Implement these security controls:

- CSRF protection on forms.
- Rate limit OTP request and verification attempts.
- Hash OTP before storing.
- Never expose raw OTP.
- Server-side prize selection only.
- Server-side geofence validation.
- Admin routes protected by authentication and authorization.
- Validate all uploaded images.
- Escape user-generated content.
- Protect spin APIs from repeated rapid requests.
- Prevent duplicate spin submissions.
- Do not trust frontend eligibility status.
- Log important events for audit.

---

## 10. UX Requirements

### Player UX

- Mobile-first layout.
- Clear step-by-step journey:
  1. Enter email.
  2. Verify OTP.
  3. Complete form.
  4. Check eligibility.
  5. Spin.
  6. View result.
- Show clear messages when:
  - OTP is wrong.
  - OTP expired.
  - Player already played.
  - Player is outside geofence.
  - Another player is currently spinning.
  - Prize is won.

### Live View UX

- Full-screen display mode.
- Idle screen when no active spin.
- Large wheel and celebration animation.
- Auto reset to idle after result display.
- Should work well on large TV/projector.

### Admin UX

- Clean dashboard.
- Simple table management.
- Clear probability and inventory warnings.
- Preview wheel before publishing.
- Preview registration form.
- Preview live-view screen.

---

## 11. API / Livewire Actions

Create routes or Livewire actions for:

- Register email.
- Send OTP.
- Verify OTP.
- Submit dynamic form.
- Check spin eligibility.
- Validate geofence.
- Start spin.
- Get active spin.
- Complete spin.
- Get spin result.

Suggested endpoints:

- `POST /api/player/register-email`
- `POST /api/player/verify-otp`
- `POST /api/player/form`
- `GET /api/spin/eligibility`
- `POST /api/spin/geofence-check`
- `POST /api/spin/start`
- `GET /api/spin/active`
- `POST /api/spin/{spin}/complete`

Use Livewire where it improves development speed and UI reactivity, but keep the spin result selection secure on the backend.

---

## 12. Broadcasting Events

Create broadcast events:

### SpinStarted

Payload:

- spin_session_id
- campaign_id
- player_display
- prize_id
- prize_name
- prize_rarity
- confetti_level
- wheel_segments
- final_angle
- spin_duration_ms
- started_at_server
- ends_at_server
- animation_seed

### SpinCompleted

Payload:

- spin_session_id
- prize_id
- prize_name
- prize_rarity
- confetti_level
- redemption_message
- completed_at_server

### SpinExpired

Payload:

- spin_session_id
- reason

---

## 13. Prize Selection Logic

Implement prize selection as a dedicated service class.

Suggested class:

- `App\Services\PrizeSelectionService`

Responsibilities:

- Load active campaign prizes.
- Exclude inactive prizes.
- Exclude out-of-stock prizes.
- Validate percentage / weight mode.
- Select prize using secure random logic.
- Reserve/decrement inventory inside transaction.
- Return selected prize and calculated wheel result.

Suggested related service classes:

- `SpinEligibilityService`
- `SpinLockService`
- `GeofenceService`
- `OtpService`
- `WheelAnimationService`

---

## 14. Testing Requirements

Add tests for important rules:

- Player cannot spin without email verification.
- Player cannot spin before completing form.
- Player cannot spin if play frequency rule blocks them.
- Player cannot spin outside geofence.
- Only one player can spin at a time.
- Prize is selected server-side.
- Out-of-stock prize cannot be selected.
- OTP expires correctly.
- OTP attempt limit works.
- `/live-view` can fetch currently active spin.
- Spin lock expires safely.

Use Laravel feature tests and unit tests for service classes.

---

## 15. Seeder / Demo Data

Create demo seed data for development:

- One admin user.
- One active campaign.
- Example prizes:
  - Thank You Voucher — Common
  - Small Gift — Uncommon
  - Discount Voucher — Rare
  - Premium Accessory — Epic
  - Grand Prize — Legendary
- Example win percentage / weight configuration.
- Example registration form fields:
  - Full name
  - Phone number
  - Branch / event location
  - Consent checkbox
- Example geofence setting.

Do not hardcode these into views. They must come from database configuration.

---

## 16. Implementation Quality

Follow these standards:

- Keep controllers thin.
- Move business logic into services.
- Use Form Requests for validation.
- Use policies / gates for admin authorization.
- Use migrations with proper indexes.
- Add database indexes for email, campaign_id, player_id, status, and created_at.
- Use clear naming.
- Add comments only where logic is not obvious.
- Avoid duplicated logic.
- Use environment variables for mail and broadcasting settings.
- Make UI responsive.
- Keep frontend animation code modular.

---

## 17. Frontend Animation Structure

Create reusable frontend modules:

- `resources/js/spin/wheel-scene.js`
  - Three.js scene setup.
  - Wheel rendering.
  - Segment rendering.
  - Lighting and camera.

- `resources/js/spin/spin-controller.js`
  - Receives server payload.
  - Calculates elapsed time.
  - Starts animation.
  - Stops at final angle.

- `resources/js/spin/confetti-controller.js`
  - Runs different confetti levels by prize rarity/config.

- `resources/js/spin/live-sync.js`
  - Subscribes to broadcast events.
  - Handles live-view realtime updates.

- `resources/js/spin/player-sync.js`
  - Handles player page spin state.

The same server payload must drive both player page and live-view page.

---

## 18. Important Edge Cases

Handle these cases:

- Two players click spin at the same time.
- Player refreshes during spin.
- Live-view opens after spin already started.
- Player loses internet during spin.
- OTP is requested repeatedly.
- OTP is guessed incorrectly many times.
- Prize inventory reaches zero.
- Admin changes prize config during active spin.
- Campaign ends while player is on spin page.
- Geolocation permission is denied.
- Browser does not support geolocation.
- Broadcasting connection drops.

For every edge case, fail safely and show a clear user message.

---

## 19. Final Deliverable

Build the full Laravel application with:

- Database migrations.
- Models and relationships.
- Admin authentication.
- Admin configuration pages.
- Player registration and OTP flow.
- Dynamic form builder and response storage.
- Spin eligibility rules.
- Geofence validation.
- Server-side prize selection.
- Global one-player-at-a-time spin lock.
- Realtime player-to-live-view synchronisation.
- Three.js wheel animation.
- Confetti.js prize celebration.
- Spin history and player history.
- Tests for core rules.
- Seed data.
- Setup instructions in README.

Prioritise correctness of business rules first, then animation polish.

---

## 20. Development Instructions For Claude Code

When implementing:

1. Start by creating the Laravel project structure.
2. Configure database, auth, Livewire, broadcasting, and frontend build.
3. Create migrations and models.
4. Implement service classes for OTP, eligibility, prize selection, geofence, spin lock, and animation payload.
5. Build player flow.
6. Build admin flow.
7. Build realtime broadcasting.
8. Build Three.js and Confetti.js frontend modules.
9. Add tests.
10. Add seeders and README setup guide.

Do not skip security rules.
Do not fake realtime sync.
Do not select prize on the frontend.
Do not allow more than one active spin at the same time.
Do not hardcode prize, form, geofence, or play rule configuration in the frontend.

