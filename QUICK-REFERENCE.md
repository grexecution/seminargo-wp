# Hotel Sync - Quick Reference

## 🚀 Quick Start (3 Steps)

```bash
# 1. Enable auto-import (via WordPress admin or script)
Visit: http://seminargo.local/enable-auto-import.php

# 2. Set up system cron
crontab -e
# Add: */5 * * * * cd /Users/gregorwallner/Local\ Sites/seminargo/app/public && wp cron event run --due-now >/dev/null 2>&1

# 3. Run initial import
cd /Users/gregorwallner/Local\ Sites/seminargo/app/public
wp seminargo import-hotels --all
```

---

## 📋 Common Commands

### Check Status
```bash
# View cron events
wp cron event list | grep seminargo

# Check auto-import enabled
wp option get seminargo_auto_import_enabled

# View import progress
wp option get seminargo_batched_import_progress --format=json

# View logs (last 10 entries)
wp option get seminargo_hotels_import_log --format=json | jq '.[-10:]'
```

### Manual Operations
```bash
# Run cron manually
wp cron event run --due-now

# Enable auto-import
wp option update seminargo_auto_import_enabled 1

# Disable auto-import
wp option update seminargo_auto_import_enabled 0

# Full import (all hotels)
wp seminargo import-hotels --all

# Reset stuck process
wp option delete seminargo_batched_import_progress
```

### Debugging
```bash
# View system cron
crontab -l

# Test cron manually
*/5 * * * * cd /Users/gregorwallner/Local\ Sites/seminargo/app/public && wp cron event run --due-now

# Check PHP settings
wp eval "echo 'Memory: ' . ini_get('memory_limit') . PHP_EOL;"
wp eval "echo 'Max Execution: ' . ini_get('max_execution_time') . 's' . PHP_EOL;"

# Check WordPress constants
wp eval "echo 'DISABLE_WP_CRON: ' . ( defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ? 'YES' : 'NO' ) . PHP_EOL;"
```

---

## 🔧 Quick Fixes

### Stuck Import
```bash
# Reset progress
wp option delete seminargo_batched_import_progress

# Or via SQL
mysql -u root -proot local -e "DELETE FROM wp_options WHERE option_name = 'seminargo_batched_import_progress';"
```

### Cron Not Running
```bash
# Verify cron is scheduled
crontab -l | grep wp

# Test manually
cd /Users/gregorwallner/Local\ Sites/seminargo/app/public
wp cron event run --due-now

# Check WordPress events
wp cron event list
```

### Clear Logs
```bash
# Via WP-CLI
wp option delete seminargo_hotels_import_log

# Or via WordPress admin:
# Hotels → Import / Sync → Clear Logs button
```

---

## 📊 Monitoring Dashboard

### WordPress Admin
**Location:** Hotels → Import / Sync

**Shows:**
- Current import status
- Progress bars (Phase 1 & 2)
- Real-time logs
- Statistics (created, updated, errors)
- Auto-import toggle

### Command Line
```bash
# One-line status check
echo "Auto-Import: $(wp option get seminargo_auto_import_enabled)" && \
echo "Status: $(wp option get seminargo_batched_import_progress --format=json | jq -r '.status // "idle"')" && \
echo "Hotels: $(wp post list --post_type=hotel --format=count)"
```

---

## 🎯 Important Files

```
wp-config.php                           # DISABLE_WP_CRON
inc/hotel-importer.php                  # Main sync logic
HOTEL-SYNC-SETUP.md                     # Full documentation
IMPLEMENTATION-SUMMARY.md               # What was changed
enable-auto-import.php                  # Quick setup (delete after use)
```

---

## 🔢 Option Names (Database)

```
seminargo_auto_import_enabled           # Enable/disable auto-import
seminargo_batched_import_progress       # Current import progress
seminargo_last_full_sync_time           # When last full sync ran
seminargo_hotels_import_log             # Log entries array
seminargo_hotels_last_import            # Last import statistics
seminargo_hotels_imported_ids           # List of imported hotel IDs
```

---

## 📞 Troubleshooting Quick Guide

| Problem | Quick Fix |
|---------|-----------|
| **Import stuck** | `wp option delete seminargo_batched_import_progress` |
| **Cron not running** | Check `crontab -l` and `wp cron event list` |
| **Out of memory** | Increase in `wp-config.php`: `define('WP_MEMORY_LIMIT', '1024M');` |
| **Images not downloading** | Check Phase 2 in logs, verify image URLs accessible |
| **Too many requests** | Reduce cron frequency (change hourly to every_six_hours) |

---

## 🔐 Security Notes

**After setup, delete:**
```bash
rm /Users/gregorwallner/Local\ Sites/seminargo/app/public/enable-auto-import.php
```

**Protect cron endpoints:**
- System cron is more secure than WP pseudo-cron
- No public access to wp-cron.php needed
- All admin operations require authentication

---

## 📈 Performance Expectations

### Incremental Sync (Hourly)
- **Duration:** 5-15 minutes
- **Hotels processed:** 100-500 typically
- **Cron executions:** 10-50

### Full Sync (Weekly)
- **Duration:** 9-10 hours
- **Hotels processed:** All (~4,000)
- **Cron executions:** ~4,000
- **Images:** ~40,000

### Resources
- **Memory:** 512M-1024M
- **PHP execution:** 60s per batch
- **Database:** ~50 queries per hotel

---

## ✅ Health Check Script

```bash
#!/bin/bash
echo "=== Hotel Sync Health Check ==="
echo ""
echo "Auto-Import: $(wp option get seminargo_auto_import_enabled)"
echo "Import Status: $(wp option get seminargo_batched_import_progress --format=json | jq -r '.status // "idle"')"
echo "Total Hotels: $(wp post list --post_type=hotel --format=count)"
echo "Cron Scheduled: $(crontab -l | grep -c wp)"
echo "DISABLE_WP_CRON: $(grep -c DISABLE_WP_CRON wp-config.php)"
echo ""
echo "Last 3 Log Entries:"
wp option get seminargo_hotels_import_log --format=json | jq -r '.[-3:][] | "\(.time) - \(.type): \(.message)"'
```

Save as `check-sync.sh`, make executable with `chmod +x check-sync.sh`, run with `./check-sync.sh`

---

**Need more details?** See `HOTEL-SYNC-SETUP.md` for complete documentation.
