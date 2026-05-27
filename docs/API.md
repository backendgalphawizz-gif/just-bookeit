# Just Book IT — Mobile API

Base URL: `http://127.0.0.1:8000/api`

All responses:

```json
{ "success": true, "message": "...", "data": { } }
```

Auth header (protected routes): `Authorization: Bearer {token}`

**OTP auth `type`:** Send and verify require `"type": "login"` or `"type": "register"`.

| Situation | Send OTP `data.type` | Message |
|-----------|----------------------|---------|
| `register` but mobile already exists | `login` | Already registered — continue with login |
| `login` but mobile not found | `register` | No account — please register |
| Matches account state | same as requested | OTP sent for login / registration |

**Verify OTP:** Use the same `type` as send. If user chose `register` but is already registered, verify returns **login** (`token`, `already_registered: true`).

**Send OTP response** includes random `otp`:

```json
{
  "mobile": "9876543210",
  "type": "login",
  "requested_type": "register",
  "is_registered": true,
  "otp": "5821",
  "message": "You are already registered with this mobile. Please continue with login."
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
