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

## Flutter / mobile integration (v1 customer)

There is **no** `GET /live-messages` API. Live delivery is WebSocket-only.

```dart
// 1) From GET /api/v1/config → data.broadcasting
final b = config['broadcasting'];
if (b['enabled'] != true) {
  // fall back to polling GET /api/v1/chats/{id}/messages
  return;
}

// 2) Connect (Pusher protocol → Reverb)
final pusher = PusherClient(
  b['key'], // e.g. c7tpsunm8sg7wyzx82wr
  PusherOptions(
    host: b['host'], // 192.168.1.69
    wsPort: b['port'], // 8080
    encrypted: b['useTLS'] == true,
    auth: PusherAuth(
      b['auth_endpoint'], // /api/v1/broadcasting/auth
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    ),
  ),
);
await pusher.connect();

// 3) Subscribe
// Inbox:
pusher.subscribe('private-chat.customer.$customerId');
// Open thread:
final channel = pusher.subscribe('private-chat.conversation.$chatId');

// 4) Listen (event names include the leading dot in Echo;
//    some Flutter Pusher packages use without the leading dot: chat.message.created)
channel.bind('chat.message.created', (event) {
  final data = jsonDecode(event.data);
  final message = data['message'];
  // append to UI; dedupe by message['id']
});

// 5) Still send/load via REST
// GET  /api/v1/chats/{chatId}/messages
// POST /api/v1/chats/{chatId}/messages
```

Vendor app is identical with `/api/v2` and channel `private-chat.vendor.$vendorId`.

## Online / offline presence

`is_online` / `online_status` on chat list/detail mean the **other party** currently has a chat thread open (not merely the app open).

### Rules

- Online only while a conversation is open.
- Chat **list** alone does **not** mark you online.
- Opening messages / sending keeps you online (server heartbeat TTL ≈ 60s).
- Explicitly leave with `status=offline` when closing the thread.

### Endpoints

| Client | Endpoint | Body |
|---|---|---|
| Customer app | `POST /api/v1/chats/presence` | `{ "status": "online" }` or `{ "status": "offline" }` |
| Vendor app | `POST /api/v2/chats/presence` | same |
| Customer web | `POST /chat/presence` | `status=online\|offline` (+ CSRF) |
| Vendor panel | `POST /vendor/chat/presence` | same |

### Flutter

```dart
// When opening a thread:
await api.post('/api/v1/chats/presence', {'status': 'online'});
// Heartbeat while the thread screen stays mounted (~25s):
Timer.periodic(Duration(seconds: 25), (_) {
  api.post('/api/v1/chats/presence', {'status': 'online'});
});
// When leaving the thread (dispose / back):
await api.post('/api/v1/chats/presence', {'status': 'offline'});
```

Chat list items already return:

```json
{
  "is_online": true,
  "online_status": "online"
}
```
