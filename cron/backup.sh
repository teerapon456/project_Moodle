#!/bin/bash

# Configuration
BACKUP_DIR="/srv/myhr/dev/backups/auto"
DATE=$(date +%Y-%m-%d_%H-%M-%S)
RETENTION_DAYS=7

# Ensure backup directory exists
mkdir -p $BACKUP_DIR

echo "[$(date)] Starting backup..."

# 1. Backup Databases (Portal & Moodle)
echo "[$(date)] Backing up Databases..."
docker exec myhr-db /usr/bin/mysqldump -u root --password='R00t_S3cur3_P@ss_2026!' --force --single-transaction --databases myhr_portal moodle | gzip > "$BACKUP_DIR/db_at_$DATE.sql.gz"

# 3. Cleanup old backups (older than 7 days)
echo "[$(date)] Cleaning up old backups..."
find $BACKUP_DIR -type f -name "*.sql.gz" -mtime +$RETENTION_DAYS -delete

echo "[$(date)] Backup completed successfully."
echo "Saved to: $BACKUP_DIR"
