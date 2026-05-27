# Just Book IT — Mobile API

Base URL: `http://127.0.0.1:8000/api`

All responses:

```json
{ "success": true, "message": "...", "data": { } }
```

Auth header (protected routes): `Authorization: Bearer {token}`

**Send OTP response:** Always includes a random 4-digit `otp` in `data` on v1, v2, and v3 (use that value in Verify OTP).

```json
"data": {
  "mobile": "9876543210",
  "otp": "5821",
  "masked_mobile": "+91 ******210",
  "expires_in_seconds": 300,
  "message": "OTP sent successfully."
}
```

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
