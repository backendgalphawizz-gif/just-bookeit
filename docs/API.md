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

**Postman:** Import `docs/postman/Just-Book-IT-API.postman_collection.json` and `docs/postman/Just-Book-IT-Local.postman_environment.json`.

**Quick test flow in Postman (v1):** Guest session → Home → Catalog List → Catalog Detail → Booking Preview → Create Booking → Payment Summary → Pay Now → Booking History.

Auto-saved variables: `v1_token`, `v1_otp`, `v1_portfolio_item_id`, `v1_designer_id`, `v1_booking_id`.

---

## API v1 — User (Customer)

### Auth

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/v1/auth/otp/send` | — | `{ "mobile", "type": "login\|register" }` |
| POST | `/v1/auth/otp/verify` | — | `{ "mobile", "otp", "type" }` → token or `registration_token` |
| POST | `/v1/auth/register` | — | `{ "registration_token", "name", "email?" }` |
| POST | `/v1/auth/guest` | — | Guest session |
| GET | `/v1/auth/me` | Bearer | Profile |
| POST | `/v1/auth/profile` | Bearer | Update profile |
| POST | `/v1/auth/logout` | Bearer | Logout |

### Home & discovery (public)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/v1/home` | Banners, services, shop categories, featured designers. `?city=` filters by vendor city. `?latitude=&longitude=` (or `lat`/`lng`) filters designers within the admin discovery radius (km). |
| GET | `/v1/config` | App config: legal, features, branding, broadcasting, and `razorpay` (`enabled`, `key_id`, `currency`). Never returns the Razorpay secret. |
| GET | `/v1/categories` | Filter shop + service together. `?category_id=` / `?parent_id=` / `?shop_category_id=` (Women/Men/Kids) **and** `?service_category_id=` / `?service=` (rented-dress, rented-jewellery, …). `?type=`, `?roots=1`. Each item includes `image_url` |

**Examples**

```http
GET /v1/categories?category_id=2&service_category_id=6
GET /v1/categories?parent_id=2&service=rented-jewellery
GET /v1/categories?roots=1&service_category_id=5
```

Combined shop + service filters return **subcategories** that match both (e.g. Women’s jewellery: Necklace, Ring).


**`GET /v1/categories?roots=1`** returns shop categories and services separately:

```json
{
  "categories": [
    { "id": 1, "name": "Women", "slug": "women", "type": "main", "parent_id": null, "image_url": "..." }
  ],
  "services": [
    { "id": 4, "name": "Fashion Designer", "slug": "fashion-designer", "type": "service", "parent_id": null, "image_url": "..." }
  ]
}
```

Upload images in **Admin → Categories** when creating or editing a category.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/v1/search` | `?q=` — catalog items + designers. Optional `?city=` or `?latitude=&longitude=` (admin radius). |
| GET | `/v1/catalog` | `?search=`, `?category_id=`, `?vendor_id=`, `?service=`, `?city=`, `?latitude=&longitude=`, `?page=`, `?per_page=` |
| GET | `/v1/catalog/{id}` | Product detail, reviews, related items |
| GET | `/v1/designers` | `?search=`, `?featured=1`, `?city=`, `?latitude=&longitude=`, `?page=` |
| GET | `/v1/designers/{id}` | Designer profile + portfolio |

**Location radius:** Admin → Settings → **Discovery radius** sets `discovery_radius_km` (default 25). When the app sends `latitude` + `longitude`, Home / Catalog / Designers / Search only return vendors (and their products) within that km. Vendors must have latitude/longitude saved. Without lat/lng, `city` filtering still works as before. Radius is also exposed on `GET /v1/config` as `features.discovery_radius_km`.

### Bookings (Bearer required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/v1/bookings` | History. `?tab=rental_dress\|rental_jewellery\|designers` |
| GET | `/v1/bookings/{id}` | Booking detail + tracking steps |
| GET | `/v1/bookings/preview/{portfolioItemId}` | Checkout preview (item, address, sizes, payment summary, cart). `?shipment_required=1`. Includes `cart_item_status.in_cart` for duplicate detection. |
| POST | `/v1/bookings` | Create booking (`multipart` if reference images). See body below. |
| POST | `/v1/bookings/{id}/cancel` | Cancel when status is `new` or `pending_acceptance` |
| GET | `/v1/bookings/addresses` | Saved addresses from past orders |

**Create booking body** (JSON or `multipart/form-data`):

```json
{
  "portfolio_item_id": 1,
  "size": "XL",
  "measurement_type": "women",
  "customer_notes": "Add custom notes...",
  "delivery_address": "G-14 1st sabari nagar, sukhliya...",
  "billing_address": "optional",
  "city": "Indore",
  "pincode": "452010",
  "rental_start_date": "2026-06-15",
  "rental_end_date": "2026-06-17",
  "shipment_required": true,
  "measure_height_cm": 165,
  "measure_chest_cm": 36,
  "measure_waist_cm": 28
}
```

Reference images: `reference_images[]` (max 5, jpeg/png/webp, 4MB each).

**Multi-item cart checkout** (`multipart/form-data` when uploading images):

| Field | Notes |
|--------|--------|
| `items_json` | **Preferred** JSON string of cart lines when also uploading `items[N][reference_images][]`. |
| `items` | JSON string of cart lines. **Do not** use this name if files are `items[N][…]` — PHP overwrites it. Use `items_json` instead, or upload files as `reference_images[N][]`. |
| Per-line `rental_start_date` / `rental_end_date` | Required on each **rental dress / jewellery** line. Omit on fashion designer lines. |
| Top-level rental dates | Optional fallback only (same window for every rental line). |
| `items[N][reference_images][]` or `reference_images[N][]` | Optional images for line N. |

**Working curl** (use `--form-string` for JSON so shells do not mangle it):

```bash
curl --location 'http://192.168.1.69:8000/api/v1/bookings' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer TOKEN' \
  --form 'delivery_address=436, bajrang nagar, Indoree' \
  --form 'city=Indoree' \
  --form 'pincode=452001' \
  --form 'shipment_required=1' \
  --form-string 'items_json=[{"cart_item_id":42,"portfolio_item_id":1,"quantity":1,"service_type":"fashion-designer","size":"XL"},{"cart_item_id":41,"portfolio_item_id":2,"quantity":1,"service_type":"rented-jewellery","rental_start_date":"2026-07-22","rental_end_date":"2026-07-24"}]' \
  --form 'items[0][reference_images][]=@/path/to/a.png' \
  --form 'items[1][reference_images][]=@/path/to/b.png'
```

**Flutter FormData** — pick one pattern:

```dart
// Option A (recommended): items_json + items[N] images
final form = FormData.fromMap({
  'delivery_address': address,
  'city': city,
  'pincode': pincode,
  'shipment_required': 1,
  'items_json': jsonEncode([
    {
      'cart_item_id': 42,
      'portfolio_item_id': 1,
      'service_type': 'fashion-designer',
      'size': 'XL',
      // no rental dates
    },
    {
      'cart_item_id': 58,
      'portfolio_item_id': 2,
      'service_type': 'rented-jewellery',
      'customer_notes': 'for the testing',
      'rental_start_date': '2026-07-22',
      'rental_end_date': '2026-07-24',
    },
  ]),
});
form.files.add(MapEntry('items[0][reference_images][]', await MultipartFile.fromFile(path0)));
form.files.add(MapEntry('items[1][reference_images][]', await MultipartFile.fromFile(path1)));

// Option B: keep field name "items", but do NOT prefix files with items[...]
final formB = FormData.fromMap({
  'items': jsonEncode(lineItems), // dates live here
});
formB.files.add(MapEntry('reference_images[0][]', await MultipartFile.fromFile(path0)));
```

**Per-item rental rules**
- `fashion-designer` → **no** `rental_start_date` / `rental_end_date` (and none required)
- `rented-dress` / `rented-jewellery` → each line must include its own dates
- Mixed carts can use different date ranges per rental line

**Single-item booking** (no cart / no `items_json`): send top-level `portfolio_item_id` + `rental_start_date` / `rental_end_date` when the product is rental dress or jewellery.

### Payment (Bearer required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/v1/payment/methods` | UPI, debit/credit card, COD (if enabled in admin) |
| GET | `/v1/payment/bookings/{id}` | Payment screen summary |
| POST | `/v1/payment/bookings/{id}/pay` | `{ "payment_method": "upi\|debit_card\|credit_card\|cod" }` |

**Payment summary fields:** `subtotal`, `shipping_fee`, `tax_percent`, `tax_amount`, `total_amount`, `currency`.

### Profile (Bearer required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/v1/profile` | Profile header + menu + counts |
| GET | `/v1/profile/pages` | About us, help text, terms, privacy, FAQ, contact |

### Addresses (Bearer required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/v1/addresses` | List saved addresses |
| POST | `/v1/addresses` | Create address |
| PUT | `/v1/addresses/{id}` | Update address |
| DELETE | `/v1/addresses/{id}` | Delete address |

**Create / update address body:**

```json
{
  "label": "Home",
  "name": "Shreya Shah",
  "country": "India",
  "house_no": "1234",
  "road_area": "ABC Colony, Near Lal Bagh",
  "city": "Indore",
  "state": "M.P",
  "pincode": "452005",
  "is_default": true
}
```

`country`, `house_no`, and `road_area` are required on create. `address_line` is optional (auto-built from house no. + road/area when omitted). Response includes `country`, `house_no`, `road_area`, `line`, and `full_address`.

### Measurements (Bearer required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/v1/measurements` | List measurement profiles |
| GET | `/v1/measurements/{id}` | Full profile (includes `extra_measurements`) |
| POST | `/v1/measurements` | Create profile |
| PUT | `/v1/measurements/{id}` | Update profile |
| DELETE | `/v1/measurements/{id}` | Delete profile |

### Chats (Bearer required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/v1/chats` | Chat list. `?search=` filters by designer name. Each item includes `is_online` / `online_status` for the designer |
| POST | `/v1/chats` | Start chat: `{ "vendor_id", "message?" }` |
| POST | `/v1/chats/presence` | Mark self online/offline while chat is open: `{ "status": "online" \| "offline" }`. Call `online` when opening a thread, `offline` when leaving. Heartbeat every ~25s while open |
| GET | `/v1/chats/{id}/messages` | Message history (marks vendor messages read; refreshes your online presence) |
| POST | `/v1/chats/{id}/messages` | Send message: `{ "body" }` or `multipart` with `attachment` |

### Help & Support (Bearer required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/v1/support-tickets` | List support requests |
| POST | `/v1/support-tickets` | `{ "subject", "email", "description" }` |
| GET | `/v1/support-tickets/{id}` | Ticket detail |

### Figma screen mapping

| Screen | API |
|--------|-----|
| Home | `GET /v1/home` |
| Shop by Category | `GET /v1/categories?roots=1` |
| Fashion Designers list | `GET /v1/catalog` or `GET /v1/designers` |
| Product detail | `GET /v1/catalog/{id}` |
| Booking overview | `GET /v1/bookings/preview/{id}` → `POST /v1/bookings` |
| Payment | `GET /v1/payment/bookings/{id}` → `POST /v1/payment/bookings/{id}/pay` |
| Booking history | `GET /v1/bookings?tab=designers` |
| Chat list | `GET /v1/chats` (`is_online` / `online_status`) |
| Chat thread | `POST /v1/chats/presence` (`online`/`offline`) → `GET /v1/chats/{id}/messages` → `POST /v1/chats/{id}/messages` |
| Profile | `GET /v1/profile` |
| Measurements | `GET/POST/PUT/DELETE /v1/measurements` |
| Address | `GET/POST/PUT/DELETE /v1/addresses` |
| Help & Support | `GET/POST /v1/support-tickets` |
| About / FAQ / Legal | `GET /v1/profile/pages` or `GET /v1/config` |

---

## API v2 — Vendor

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/v2/auth/otp/send` | Send OTP |
| POST | `/v2/auth/otp/verify` | Verify OTP |
| POST | `/v2/auth/register` | Same fields as web vendor register. `multipart`: `registration_token`, `owner_name`, `email`, `shop_name` (or `brand_name`), `service_types[]`, bank fields, `aadhar_front`/`aadhar_back` (required), optional `aadhar_number`, location, `latitude`, `longitude`, `gst_no`, `cover_image`, `profile_image`, `shop_logo`, `pan_card`. Returns Bearer `token`. Status `active`. |
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
