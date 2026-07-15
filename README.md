# 🤖 Wa SubTrack Bot

Bot WhatsApp pengingat tagihan langganan otomatis dengan natural language parsing.

## Features

- **Natural Language Input** — Kirim pesan bebas, bot parse otomatis
- **Multi-Currency** — Otomatis konversi USD/EUR ke IDR
- **Auto Reminders** — Pengingat H-3 dan H-1 sebelum jatuh tempo
- **Easy Management** — List, total, hapus langganan via chat

## Quick Start

```bash
# Clone
git clone https://github.com/verifydream/wa-subtrack-bot.git
cd wa-subtrack-bot

# Setup
cp .env.production .env
# Edit .env — isi WHATSAPP_TOKEN dari Fonnte

# Deploy
make deploy
```

## Usage

| Command | Description |
|---------|-------------|
| `Netflix 149 rb tanggal 15` | Tambah langganan |
| `Hosting digitalocean 15 dollar tiap tanggal 1` | Multi-currency |
| `list` / `daftar` | Lihat semua langganan |
| `total` | Total biaya/bulan |
| `hapus [nama]` | Hapus langganan |
| `help` | Bantuan |

## Parser Examples

```
"Netflix 149 rb tanggal 15"          → Netflix, Rp 149.000, tgl 15
"Hosting digitalocean 15 dollar tgl 1" → DigitalOcean, $15, tgl 1
"Spotify premium 55000 tanggal 20"    → Spotify, Rp 55.000, tgl 20
"VPS 1.5jt tanggal 1"                → VPS, Rp 1.500.000, tgl 1
```

## Tech Stack

- **Backend:** Laravel 11 (PHP 8.3)
- **Database:** SQLite (default) / MySQL
- **WhatsApp API:** [Fonnte](https://fonnte.com)
- **Scheduler:** Laravel Task Scheduler (cron)
- **Currency API:** exchangerate-api.com
- **Deploy:** Docker

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/health` | Health check |
| `POST` | `/api/webhook` | Fonnte WhatsApp webhook |

### Webhook Payload

```json
{
    "phone": "6281234567890",
    "message": "Netflix 149 rb tanggal 15"
}
```

## Deployment

```bash
make deploy      # Build + run container
make logs        # Lihat logs
make health      # Cek health
make restart     # Restart container
make migrate     # Jalankan migration
make shell       # Masuk container
```

### Environment Variables

| Variable | Description | Required |
|----------|-------------|----------|
| `APP_KEY` | Laravel app key | Auto-generated |
| `WHATSAPP_API_URL` | Fonnte API URL | Default: `https://api.fonnte.com/send` |
| `WHATSAPP_TOKEN` | Fonnte API token | ✅ Yes |

## Fonnte Setup

1. Daftar di [fonnte.com](https://fonnte.com)
2. Connect nomor WhatsApp (scan QR)
3. Settings → Webhook → `http://YOUR_IP:8081/api/webhook`
4. Test kirim pesan

## Project Structure

```
├── app/
│   ├── Console/Commands/    # Artisan commands
│   ├── Http/Controllers/    # Webhook handler
│   ├── Models/              # Eloquent models
│   └── Services/            # Business logic
│       ├── MessageParser.php    # NLP text parser
│       ├── CurrencyService.php  # USD/EUR → IDR
│       └── WhatsAppService.php  # Fonnte API client
├── database/migrations/     # DB migrations
├── docs/                    # Documentation
├── routes/                  # API routes
├── Dockerfile               # Docker build
├── Makefile                 # Deployment commands
└── .pr_agent.toml           # PR-Agent config
```

## License

MIT
