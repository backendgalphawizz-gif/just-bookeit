# Just Book IT — Mobile API

Base URL: `http://127.0.0.1:8000/api`

All responses:

```json
{ "success": true, "message": "...", "data": { } }
```

Auth header (protected routes): `Authorization: Bearer {token}`

**OTP in API response (testing only)**

| Environment | `otp` in Send OTP response? |
|-------------|----------------------------|
| Local (`APP_ENV=local`) | Yes (automatic) |
| Live (`APP_ENV=production`) | **No** — unless you enable test mode (below) |

**Local / staging:** Works automatically when `APP_ENV` is not `production`.

**Live server (Postman / QA):** Add to the server `.env`:

```env
API_OTP_TEST_MODE=true
API_OTP_DEBUG_CODE=1234
```

Then on the server run:

```bash
php artisan config:clear
```

If you use config cache in production, rebuild after changing `.env`:

```bash
php artisan config:cache
```

Send OTP will then return:

```json
"data": {
  "mobile": "3333333333",
  "otp": "1234",
  "debug_otp": "1234",
  "test_mode": true,
  ...
}
```

Turn off before real users: `API_OTP_TEST_MODE=false` and `config:clear` (never leave this on public production long-term).

**Postman:** Import `docs/postman/Just-Book-IT-API.postman_collection.json` and `docs/postman/Just-Book-IT-Local.postman_environment.json`. Tokens are saved automatically after Verify OTP / Register / Guest.

---

## API v1 — User (Customer)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/v1/auth/otp/send` | `{ "mobile": "+91 9512345678" }` |
| POST | `/v1/auth/otp/verify` | `{ "mobile", "otp" }` → token or `registration_token` |
| POST | `/v1/auth/register` | `{ "registration_token", "name", "email?" }` |
| POST | `/v1/auth/guest` | Guest session |
| GET | `/v1/auth/me` | Profile (auth) |
| POST | `/v1/auth/logout` | Logout (auth) |

---

## API v2 — Vendor

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/v2/auth/otp/send` | Send OTP |
| POST | `/v2/auth/otp/verify` | Verify OTP |
| POST | `/v2/auth/register` | `{ "registration_token", "brand_name", "owner_name", "email", "city?" }` |
| GET | `/v2/auth/me` | Profile (auth) |
| POST | `/v2/auth/logout` | Logout (auth) |

---

## API v3 — Driver

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/v3/auth/otp/send` | Send OTP |
| POST | `/v3/auth/otp/verify` | Verify OTP |
| POST | `/v3/auth/register` | `multipart`: `registration_token`, `name`, `email?`, `city?`, `aadhar` (image) |
| GET | `/v3/auth/me` | Profile (auth) |
| POST | `/v3/auth/logout` | Logout (auth) |

Driver accounts start as **pending** until approved in admin **Operations → Drivers**.
