# Setup Guide

## Prerequisites

- PHP 8.3+
- Composer
- Docker (optional)
- Fonnte account

## 1. Clone & Install

```bash
git clone https://github.com/verifydream/wa-subtrack-bot.git
cd wa-subtrack-bot
composer install
```

## 2. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```
WHATSAPP_API_URL=https://api.fonnte.com/send
WHATSAPP_TOKEN=your_fonnte_token_here
```

## 3. Database

```bash
php artisan migrate
```

## 4. Run

```bash
# Development
php artisan serve --port=8081

# Production (Docker)
make deploy
```

## 5. Fonnte Configuration

1. Go to https://fonnte.com
2. Register / Login
3. Connect your WhatsApp number (scan QR code)
4. Go to Settings → Webhook
5. Set URL: `http://YOUR_SERVER_IP:8081/api/webhook`
6. Save

## 6. Test

Send a message to your connected WhatsApp number:
```
Netflix 149 rb tanggal 15
```

Expected response:
```
✅ Tercatat!

Netflix
💰 Rp 149.000/bulan
📅 Tagihan tanggal 15
⏰ Reminder H-3 & H-1 sebelum jatuh tempo
```

## Docker Deployment

```bash
# Build
docker build -t wa-subtrack-bot .

# Run
docker run -d \
  --name subtrack-bot \
  --restart unless-stopped \
  -p 8081:8081 \
  wa-subtrack-bot

# Logs
docker logs -f subtrack-bot
```

## Scheduler Setup

The Laravel scheduler runs automatically inside the Docker container via cron.

For manual testing:
```bash
php artisan subtrack:reminders
```

This sends H-3 and H-1 reminders for all active subscriptions.
