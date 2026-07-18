# 🤖 Wa SubTrack Bot

Bot WhatsApp pengingat tagihan langganan otomatis.

## Features

- **Natural language parsing:** Kirim pesan bebas, bot parse otomatis
- **Multi-currency (IDR/USD/EUR):** Otomatis konversi USD dan EUR ke IDR
- **Auto reminders H-3 & H-1:** Pengingat sebelum jatuh tempo
- **Easy management via chat:** list/total/hapus langganan langsung via chat

## Message Flow

1. User sends WhatsApp message
2. Fonnte webhook receives message and forwards to application
3. `WhatsAppController.webhook()` handles the incoming payload
4. `MessageParser.parse()` extracts service name, amount, currency, and billing day
5. `CurrencyService.toIDR()` converts foreign currencies to IDR
6. Data is saved to the Database (DB)
7. `WhatsAppService.sendText()` replies back to the user to confirm

## Commands

| Command/Input | Description | Result |
|---|---|---|
| `Netflix 149 rb tanggal 15` | Adds Rp 149.000/month, billing day 15 | Netflix, Rp 149.000, tgl 15 |
| `Hosting digitalocean 15 dollar tiap tanggal 1` | $15/month, billing day 1 | DigitalOcean, $15, tgl 1 |
| `Spotify premium 55000 tanggal 20` | Rp 55.000/month, billing day 20 | Spotify, Rp 55.000, tgl 20 |
| `VPS 1.5jt tanggal 1` | Rp 1.500.000/month, billing day 1 | VPS, Rp 1.500.000, tgl 1 |
| `list` / `daftar` | Shows all active subscriptions | List of active subs |
| `total` | Total monthly cost in IDR | Sum of all active subs in IDR |
| `hapus [name]` | Deactivates subscription | Subscription removed |
| `help` | Shows help | Help message |

## Parser Logic Breakdown

- **Amount extraction:**
  - `rb` / `ribu` (×1000)
  - `jt` / `juta` (×1.000.000)
  - `dollar` / `USD`
  - `euro` / `EUR`
  - plain large number (4+ digits)
- **Currency detection:**
  - `$` = USD
  - `"dollar"`/`"usd"` = USD
  - `"euro"`/`"eur"` = EUR
  - else = IDR
- **Billing day:**
  - `"tanggal X"`
  - `"tgl X"`
  - `"tiap tanggal X"`

## Environment Variables

- `WHATSAPP_TOKEN`: Token from Fonnte API
- DB connection variables (`DB_CONNECTION`, etc.)

## Docker Deployment

1. Make sure you have Docker and Docker Compose installed.
2. Clone the repository and navigate to the project directory.
3. Setup the `.env` file:
   ```bash
   cp .env.example .env
   ```
   Fill in the `WHATSAPP_TOKEN` and database connection.
4. Use Makefile commands to deploy:
   ```bash
   make deploy      # Build and run the container
   make migrate     # Run database migrations
   ```
   Other commands available: `make logs`, `make health`, `make restart`, `make shell`.
