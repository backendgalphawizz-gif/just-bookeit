# Admin Panel — Module Roadmap

Maps proposal sections to Laravel implementation phases.

## Phase 1 — Foundation (current)

- [x] Admin authentication (email/username + password, remember me)
- [x] RBAC tables (roles, permissions, role_permission)
- [x] Login history (`admin_login_logs`)
- [x] Activity log table (`admin_activity_logs`)
- [x] Dashboard with live stats, charts, activity feed
- [x] CRUD: Customers, Vendors, Categories, Orders, Refunds, Disputes, Banners
- [x] Payments list/view (from orders)
- [x] Vendor approve / reject / suspend actions
- [x] RBAC enforced on all admin routes
- [ ] Forgot password / OTP (optional per spec)
- [ ] Permission middleware on routes
- [ ] Session timeout & login attempt limits (System Settings)

## Phase 2 — Core operations

| # | Module | Key tables (planned) |
|---|--------|----------------------|
| 3 | Customer Management | `customers`, `addresses` |
| 4 | Vendor Management | `vendors`, `vendor_applications`, `vendor_bank_details` |
| 5 | Portfolio Moderation | `portfolio_items`, `portfolio_reviews` |
| 6 | Category Management | `categories`, `category_banners` |
| 7 | Booking & Orders | `orders`, `order_status_logs`, `order_measurements` |

## Phase 3 — Money & communication

| # | Module |
|---|--------|
| 8 | Chat & Communication |
| 9 | Video Call Monitoring |
| 10 | Payment Management |
| 11 | Refund Management |
| 12 | Vendor Payout Management |
| 13 | Commission Management |

## Phase 4 — Platform

| # | Module |
|---|--------|
| 14 | Banner & CMS |
| 15 | Notification Management |
| 16 | Reports & Analytics |
| 17 | Dispute Management |
| 18 | System Settings |

## Roles (RBAC)

- Super Admin — full access
- Support Admin — customers, orders, chat, disputes, refunds
- Finance Admin — payments, refunds, payouts, commissions
- Vendor Management Admin — vendors, portfolio approvals
- Content Moderator — portfolio, banners, CMS

## API layer (later)

REST/JSON APIs under `/api/v1` for Flutter user app, vendor app, and website — shared MySQL schema with admin panel.
