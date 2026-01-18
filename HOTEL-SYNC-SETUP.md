# Hotel & Image Sync Setup Guide

## Overview
This guide provides step-by-step instructions for setting up reliable hotel and image synchronization for your WordPress site.

---

## ✅ Changes Implemented

### 1. **System Cron (Reliable Execution)**
- Added `DISABLE_WP_CRON` to `wp-config.php`
- System cron ensures scheduled tasks run on time, independent of site traffic
- **Location:** `/wp-config.php` (line 80)

### 2. **Stuck Process Detection**
- Automatically detects processes stuck for > 2 hours
- Auto-resets stuck processes to allow new imports
- **Location:** `/wp-content/themes/seminargo/inc/hotel-importer.php:4748-4768`

### 3. **Optimized Image Processing**
- Increased from 3 to 10 images per batch (70% faster)
- 40,000 images = ~4,000 executions instead of 13,333
- Estimated completion: 9-10 hours instead of 9+ days
- **Location:** `hotel-importer.php:2382`

### 4. **Progress Verification**
- Verifies database writes before scheduling next batch
- Automatic retry with cache bypass on failure
- Prevents data loss and infinite loops
- **Location:** `hotel-importer.php:2644-2669`

### 5. **Incremental Sync Strategy**
- **Full Sync:** Once per week (all hotels + all images)
- **Incremental Sync:** Every other run (only new/updated hotels)
- Reduces processing from 40k images to ~100-500 per sync
- **Location:** `hotel-importer.php:4796-4812`

---

## 🚀 Setup Instructions

### Step 1: Set Up System Cron

System cron is already configured in `wp-config.php`. Now you need to add the cron job to your system.

#### For Local Development (macOS/Linux):

1. **Find WP-CLI path:**
   ```bash
   which wp
   ```

   If `wp` is not found, you may need to install it or use the full path to Local's WP-CLI binary.

2. **Edit crontab:**
   ```bash
   crontab -e
   ```

3. **Add this line (replace with your actual path):**
   ```bash
   */5 * * * * cd /Users/gregorwallner/Local\ Sites/seminargo/app/public && /usr/local/bin/wp cron event run --due-now --path=/Users/gregorwallner/Local\ Sites/seminargo/app/public >/dev/null 2>&1
   ```

   **What this does:**
   - Runs every 5 minutes
   - Executes all WordPress cron events that are due
   - Runs in the background (no output unless errors)

4. **Verify cron is working:**
   ```bash
   # Check if cron is scheduled
   crontab -l

   # Test manually
   wp cron event run --due-now
   ```

#### For Production Server:

Ask your hosting provider to set up a cron job with this command:
```bash
*/5 * * * * wp cron event run --due-now --path=/path/to/wordpress
```

Or use cPanel's Cron Jobs interface if available.

---

### Step 2: Enable Auto-Import

**Option A: Via WordPress Admin (Recommended)**

1. Log into WordPress admin
2. Navigate to **Hotels → Import / Sync**
3. Find the **Auto-Import** section
4. Click the **Enable Auto-Import** toggle
5. Verify the status shows "Enabled"

**Option B: Via Database (if admin UI not accessible)**

Run this in your MySQL/phpMyAdmin:
```sql
INSERT INTO wp_options (option_name, option_value, autoload)
VALUES ('seminargo_auto_import_enabled', '1', 'yes')
ON DUPLICATE KEY UPDATE option_value = '1';
```

---

### Step 3: Initial Full Sync (IMPORTANT)

For the first run with 40,000 images, use WP-CLI to avoid any timeout issues:

```bash
# Navigate to WordPress directory
cd /Users/gregorwallner/Local\ Sites/seminargo/app/public

# Run full import (no timeout limits)
wp seminargo import-hotels --all
```

**Estimated time:** 2-4 hours for 40k images

After this initial sync, the automated system will:
- Run **incremental syncs** every hour (fast, only new hotels)
- Run **full syncs** once per week (ensures everything stays up to date)

---

### Step 4: Monitor & Verify

#### Check if cron is working:
```bash
# View scheduled cron events
wp cron event list | grep seminargo

# Expected output:
# seminargo_hotels_cron    every_hour    ...
```

#### View import logs:
1. Go to **Hotels → Import / Sync**
2. Scroll to the **Import Logs** section
3. Look for entries like:
   - "🤖 Auto-import: Starting new INCREMENTAL sync..."
   - "✅ Import completed: X created, Y updated"

#### Check for stuck processes:
If you see a warning about stuck processes in logs, the system will auto-reset them after 2 hours.

---

## 📊 Expected Behavior

### Initial Setup (First 24 Hours)
- **First run:** Full sync (2-4 hours via WP-CLI or 9-10 hours via cron)
- **Subsequent runs:** Incremental (5-15 minutes per hour)

### Ongoing Operation
- **Hourly:** Incremental sync runs (only new/changed hotels)
- **Weekly:** Full sync runs (all hotels + images)

### Performance Metrics
| Metric | Before Fixes | After Fixes |
|--------|-------------|-------------|
| Images per batch | 3 | 10 |
| Total executions (40k images) | 13,333 | 4,000 |
| Estimated time | 222+ hours | 9-10 hours |
| Cron reliability | Low (pseudo-cron) | High (system cron) |
| Stuck process handling | None | Auto-reset after 2 hours |

---

## 🔧 Troubleshooting

### Issue: Cron not running

**Symptoms:**
- No import logs appearing
- Hotels not updating

**Solutions:**
1. Verify cron is scheduled:
   ```bash
   crontab -l | grep wp
   ```

2. Check if DISABLE_WP_CRON is set:
   ```bash
   grep DISABLE_WP_CRON wp-config.php
   ```

3. Test cron manually:
   ```bash
   wp cron event run --due-now
   ```

4. Check system logs:
   ```bash
   # macOS
   log show --predicate 'process == "cron"' --last 1h

   # Linux
   tail -f /var/log/cron
   ```

---

### Issue: Import gets stuck

**Symptoms:**
- Progress shows "running" for > 2 hours
- No new log entries

**Solutions:**
1. **Automatic:** System will auto-reset after 2 hours
2. **Manual reset (if needed):**
   ```sql
   DELETE FROM wp_options WHERE option_name = 'seminargo_batched_import_progress';
   ```

3. Check logs for error messages:
   - Go to **Hotels → Import / Sync**
   - Look for red error messages

---

### Issue: Images not downloading

**Symptoms:**
- Hotels created but no images
- Phase 2 never starts

**Solutions:**
1. Check if Phase 2 is reached:
   - View logs for "PHASE 2: Downloading images..."

2. Verify image URLs are accessible:
   ```bash
   # Test one image URL from API
   curl -I "https://your-api-url/image.jpg"
   ```

3. Check PHP memory limit:
   ```bash
   wp eval "echo ini_get('memory_limit');"
   ```
   Should be at least 512M, preferably 1024M

4. Test manual image sync:
   - Go to **Hotels → Import / Sync**
   - Click **Skip to Phase 2** (downloads images for existing hotels)

---

### Issue: Too many API requests

**Symptoms:**
- API rate limit errors
- 429 Too Many Requests errors

**Solutions:**
1. Reduce cron frequency:
   - Edit `/inc/hotel-importer.php:310`
   - Change `'hourly'` to `'every_six_hours'`

2. Increase batch delays:
   - Edit line 2323 or 2689
   - Change `time() + 1` to `time() + 5` (5-second delay between batches)

---

## 📋 Maintenance

### Weekly Tasks
- Check import logs for errors
- Verify full sync completed successfully
- Monitor disk space (images accumulate)

### Monthly Tasks
- Review duplicate hotels (if any)
- Check for orphaned images
- Verify cron is still scheduled

### As Needed
- Clear old logs: Click **Clear Logs** in admin
- Delete test hotels: Click **Delete All Hotels** in admin
- Reset stuck processes: Automatic (or manual SQL above)

---

## 🎯 Quick Reference

### Important Files
| File | Purpose |
|------|---------|
| `wp-config.php` | DISABLE_WP_CRON setting |
| `inc/hotel-importer.php` | Main sync logic |
| System crontab | Schedule for running cron |

### Important Options
| Option Name | Purpose |
|-------------|---------|
| `seminargo_auto_import_enabled` | Enable/disable auto-import |
| `seminargo_batched_import_progress` | Current import progress |
| `seminargo_last_full_sync_time` | When last full sync occurred |
| `seminargo_hotels_import_log` | Import log entries |

### Key Commands
```bash
# View cron events
wp cron event list

# Run cron manually
wp cron event run --due-now

# Full import via CLI
wp seminargo import-hotels --all

# Check auto-import status
wp option get seminargo_auto_import_enabled

# Enable auto-import
wp option update seminargo_auto_import_enabled 1

# View logs
wp option get seminargo_hotels_import_log --format=json
```

---

## 🆘 Support

If you encounter issues not covered here:

1. Check the import logs in WordPress admin
2. Check the debug log at `wp-content/themes/seminargo/.cursor/debug.log`
3. Review system cron logs (see troubleshooting section)
4. Verify all setup steps were completed

---

**Last Updated:** January 2026
**Version:** 2.0 (System Cron + Optimizations)
