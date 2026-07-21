# Live chat WebSockets (Laravel Reverb)

Just Book IT uses **Laravel Reverb** (Pusher protocol) so chat updates instantly on:

- Customer web (`/chat`)
- Vendor panel (`/vendor/chat`)
- Customer mobile app (API v1)
- Vendor mobile app (API v2)

Polling remains as a slow fallback.

## Run locally

1. Ensure `.env` has:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_CLIENT_HOST=
```

2. Start Reverb (separate terminal from `php artisan serve`):

```bash
php artisan reverb:start
```

3. Open customer chat and vendor chat — new messages should appear without refresh.

On phones / other devices on your LAN, leave `REVERB_CLIENT_HOST` empty so the client uses the same host as the page (e.g. `192.168.1.69`). Allow inbound TCP **8080** on the machine running Reverb.

## Channels

| Channel | Who can join | Purpose |
|---|---|---|
| `private-chat.conversation.{id}` | Conversation customer + vendor | Live messages in an open thread |
| `private-chat.customer.{id}` | That customer | Inbox / thread list updates |
| `private-chat.vendor.{id}` | That vendor | Inbox / thread list updates |

## Events

| Event name | When |
|---|---|
| `.chat.message.created` | Message sent |
| `.chat.message.updated` | Message edited |
| `.chat.message.deleted` | Message deleted |

Payload shape:

```json
{
  "event": "created",
  "conversation_id": 2,
  "message": {
    "id": 10,
    "conversation_id": 2,
    "body": "Hello",
    "sender_type": "customer",
    "sender_id": 34,
    "attachment_url": null,
    "attachment_type": null,
    "attachment_name": null,
    "is_edited": false,
    "sent_at": "7:05 AM",
    "created_at": "2026-07-21T07:05:00+00:00"
  },
  "thread": {
    "id": 2,
    "customer_id": 34,
    "vendor_id": 5,
    "customer_name": "...",
    "vendor_name": "...",
    "preview": "Hello",
    "time": "7:05 AM",
    "last_message_at": "..."
  }
}
```

Clients set `is_mine` with `message.sender_type === viewerRole`.

## Mobile / app auth

1. Read broadcasting settings from config:
   - Customer: `GET /api/v1/config` → `data.broadcasting`
   - Vendor: `GET /api/v2/config` → `data.broadcasting`
2. Connect with a Pusher-compatible client (Laravel Echo, `pusher-websocket-flutter`, etc.) using `key`, `host`, `port`, `scheme`.
3. Authorize private channels:
   - Customer: `POST /api/v1/broadcasting/auth` with `Authorization: Bearer {token}`
   - Vendor: `POST /api/v2/broadcasting/auth` with `Authorization: Bearer {token}`
4. Subscribe to:
   - `private-chat.conversation.{chatId}` while a thread is open
   - `private-chat.customer.{customerId}` or `private-chat.vendor.{vendorId}` for inbox updates
5. Listen for `.chat.message.created` / `.updated` / `.deleted` (leading dot required).

Web / vendor panel auth uses session cookie against `POST /broadcasting/auth`.
