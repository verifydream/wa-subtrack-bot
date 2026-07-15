# Deployment Guide

## Docker (Recommended)

### First Deploy

```bash
# Clone
git clone https://github.com/verifydream/wa-subtrack-bot.git
cd wa-subtrack-bot

# Setup env
cp .env.production .env
# Edit .env — set WHATSAPP_TOKEN

# Build & run
make deploy
```

### Subsequent Updates

```bash
git pull origin main
make deploy
```

### Commands

| Command | Description |
|---------|-------------|
| `make deploy` | Full rebuild + deploy |
| `make build` | Build Docker image only |
| `make run` | Start container |
| `make stop` | Stop container |
| `make restart` | Restart container |
| `make logs` | Follow logs |
| `make health` | Check health endpoint |
| `make shell` | Open shell in container |
| `make migrate` | Run migrations |

## Manual (Without Docker)

```bash
# Install
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

# Run
php artisan serve --port=8081

# Scheduler (add to crontab)
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_NAME` | Application name | `SubTrack Bot` |
| `APP_ENV` | Environment | `production` |
| `APP_DEBUG` | Debug mode | `false` |
| `APP_TIMEZONE` | Timezone | `Asia/Jakarta` |
| `DB_CONNECTION` | Database driver | `sqlite` |
| `WHATSAPP_API_URL` | Fonnte API URL | `https://api.fonnte.com/send` |
| `WHATSAPP_TOKEN` | Fonnte API token | Required |

## Firewall

```bash
# Allow webhook port
sudo ufw allow 8081/tcp
```

## Monitoring

```bash
# Health check
curl http://localhost:8081/api/health

# Container status
docker ps --filter name=subtrack-bot

# Logs
docker logs -f subtrack-bot
```
