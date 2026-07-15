# API Documentation

## Base URL

```
http://YOUR_IP:8081
```

## Endpoints

### Health Check

```
GET /api/health
```

Response:
```json
{
    "status": "ok",
    "service": "subtrack-bot"
}
```

### Webhook (WhatsApp)

```
POST /api/webhook
Content-Type: application/json
```

Request body:
```json
{
    "phone": "6281234567890",
    "message": "Netflix 149 rb tanggal 15"
}
```

Response:
```json
{
    "status": "ok"
}
```

### Supported Commands

#### Add Subscription

Send any text message with service name, amount, and billing day:

```
Netflix 149 rb tanggal 15
Hosting digitalocean 15 dollar tiap tanggal 1
Spotify premium 55000 tanggal 20
VPS 1.5jt tanggal 1
```

#### List Subscriptions

```
list
daftar
```

Response:
```
📋 *Daftar Langganan Aktif*

1. *Netflix* — Rp 149.000 (tgl 15)
2. *Hosting Digitalocean* — $15.00 (tgl 1)
3. *Spotify Premium* — Rp 55.000 (tgl 20)

💰 *Total/bulan:* Rp 475.205
```

#### Total Cost

```
total
```

Response:
```
💰 *Total Langganan/Bulan*

Rp 475.205

Ketik *list* untuk melihat detail.
```

#### Delete Subscription

```
hapus netflix
del spotify
delete digitalocean
```

#### Help

```
help
bantuan
```

### Error Responses

Invalid webhook payload:
```json
{
    "status": "ignored"
}
```

Unknown command:
```
Maaf, saya tidak mengerti. Ketik *help* untuk bantuan.
```

Subscription not found:
```
❌ Langganan "xyz" tidak ditemukan.
```
