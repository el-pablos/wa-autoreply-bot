#!/bin/bash
set -euo pipefail
# Backup dipicu oleh artisan backup:run — lihat app/Console/Commands/BackupRun.php
# Script ini adalah wrapper yang dipanggil dari server cihuy via SSH.
# Phase B5 agent akan mengisi logic lebih spesifik di sini.
docker compose -f /var/www/wa-autoreply-bot/docker-compose.prod.yml exec -T dashboard php artisan backup:run "$@"
