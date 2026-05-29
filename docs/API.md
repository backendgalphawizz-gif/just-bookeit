# Just Book IT — Mobile API

Base URL: `http://127.0.0.1:8000/api` (must include `/api` — not the site root alone)

**404 Not Found?** Check:
1. Method is **POST** (not GET)
2. Full path: `{APP_URL}/api/v2/auth/otp/send` (example for vendor)
3. `base_url` in Postman = `https://your-domain.com/api` (with `/api` at the end)
4. Latest code is deployed (`routes/api.php` exists on the server)
5. Run `php artisan route:list --path=api` on the server to confirm routes exist

All responses:

```json
{ "success": true, "message": "...", "data": { } }
```

Auth header (protected routes): `Authorization: Bearer {token}`

**OTP auth `type`:** Send and verify require `"type": "login"` or `"type": "register"`.

| Request | Account exists? | Result |
|---------|-------------------|--------|
| `type: "register"` | Yes | **422 error** — *You are already registered. Please login first.* (no OTP sent) |
| `type: "login"` | No | **422 error** — *No account found with this mobile. Please register first.* (no OTP sent) |
| `type: "register"` | No | OTP sent → verify → `registration_token` |
| `type: "login"` | Yes | OTP sent → verify → `token` |

Use the **same `type`** on send and verify.

**Send OTP response** includes random `otp`:

**422 example** (`type: register` but already registered):

```json
{
  "message": "You are already registered. Please login first.",
  "errors": {
    "type": ["You are already registered. Please login first."]
  }
}
```

**Postman:** Import `docs/postman/Just-Book-IT-API.postman_collection.json` and `docs/postman/Just-Book-IT-Local.postman_environment.json`. Tokens are saved automatically after Verify OTP / Register / Guest.

---

## API v1 — User (Customer)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/v1/auth/otp/send` | `{ "mobile", "type": "login\|register" }` |
| POST | `/v1/auth/otp/verify` | `{ "mobile", "otp", "type" }` → token or `registration_token` |
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
| POST | `/v2/auth/register` | `multipart`: `registration_token`, `brand_name`, `owner_name`, `email`, `city?`, `aadhar_front`, `aadhar_back` (images) |
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
