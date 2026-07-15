# Architecture

## Overview

```
WhatsApp User
    │
    ▼
[Fonnte API] ──webhook──▶ [Laravel App]
                              │
                    ┌─────────┼─────────┐
                    ▼         ▼         ▼
             Controller   Services   Models
                    │         │         │
                    ▼         ▼         ▼
              WhatsApp   Message    Subscription
              Controller Parser     User
                    │         │
                    ▼         ▼
              Fonnte API  SQLite DB
```

## Request Flow

1. User sends WhatsApp message
2. Fonnte receives message, forwards to webhook URL
3. `WhatsAppController::webhook()` processes request
4. `MessageParser` extracts: service name, amount, currency, billing day
5. `CurrencyService` converts to IDR if needed
6. `Subscription` model saved to database
7. Response sent back via `WhatsAppService` → Fonnte API

## Scheduler Flow

1. Cron runs `php artisan schedule:run` every minute
2. `subtrack:reminders` command runs at 8am and 6pm WIB
3. Checks all active subscriptions for H-3 and H-1 dates
4. Sends reminder messages via Fonnte API

## Message Parser

The `MessageParser` service uses regex-based NLP to extract:

- **Service name** — extracted by removing amounts, dates, and currency
- **Amount** — supports formats: `149 rb`, `15 dollar`, `55000`, `Rp 149000`
- **Currency** — detected from keywords: `$`, `dollar`, `usd`, `euro`
- **Billing day** — extracted from: `tanggal 15`, `tgl 1`, `tiap tanggal 1`

### Supported Formats

| Input | Amount | Currency |
|-------|--------|----------|
| `149 rb` | 149,000 | IDR |
| `149 ribu` | 149,000 | IDR |
| `15k` | 15,000 | IDR |
| `1.5jt` | 1,500,000 | IDR |
| `15 dollar` | 15 | USD |
| `55000` | 55,000 | IDR |
| `Rp 149000` | 149,000 | IDR |
