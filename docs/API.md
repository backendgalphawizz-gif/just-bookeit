# Just Book IT — Mobile API

Base URL: `http://127.0.0.1:8000/api`

All responses:

```json
{ "success": true, "message": "...", "data": { } }
```

Auth header (protected routes): `Authorization: Bearer {token}`

**Development OTP:** `1234` (see `API_OTP_DEBUG_CODE` in `.env`)

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
