# Hotel Import System - Architecture & Features

## Overview

The hotel import system syncs hotel data from the seminargo GraphQL API to WordPress custom post types. It handles 4815+ hotels with full metadata, images, and meeting rooms.

## Core Architecture

### Data Flow

```
API (GraphQL)
  ↓
Fetch Hotels (batched, 200 at a time)
  ↓
Process Hotels (create/update posts + metadata)
  ↓
Download Images (with deduplication)
  ↓
Draft Removed Hotels (not in API anymore)
  ↓
Complete
```

### Import Methods

1. **Manual "Fetch Now"**: Batched background processing via WP Cron (WP Engine compatible)
2. **Auto-Import**: Hourly cron that syncs hotel data (skips images for speed)
3. **WP-CLI**: `wp seminargo import-hotels` for one-time full import

## Key Features (DO NOT REMOVE)

### 1. API Slug Handling ✅
- **Location**: `process_hotel()` line ~1949
- **What**: Uses API-provided slug as WordPress post slug
- **Why**: API slugs are pre-validated and consistent
- **Code**:
  ```php
  $wp_slug = ! empty( $hotel->slug ) ? sanitize_title( $hotel->slug ) : sanitize_title( $hotel_title );
  ```

### 2. Finder URLs ✅
- **Location**: `update_hotel_meta()` lines ~2050-2053
- **What**: Stores 4 finder URLs per hotel for integration
- **URLs**:
  - `finder_url_slug`: Show hotel by slug
  - `finder_url_refcode`: Show hotel by refcode
  - `finder_add_slug`: Add hotel by slug
  - `finder_add_refcode`: Add hotel by refcode
- **Base URL**: `https://finder.dev.seminargo.eu/`

### 3. Image Download with Deduplication ✅
- **Location**: `process_hotel_images()` lines ~2330-2410
- **What**: Downloads images, bypasses WordPress security, generates thumbnails
- **Features**:
  - URL encoding for spaces/special chars
  - Fixes broken UTF-8 encoding from API (`%C3_` → `%C3%9F`)
  - MIME type detection from file content
  - Adds proper file extensions
  - Deduplication by source URL (skips re-downloading existing images)
  - Direct `wp_insert_attachment()` (bypasses upload restrictions)

### 4. Smart Change Detection ✅
- **Location**: `has_real_change()` lines ~2538-2573
- **What**: Detects real changes, ignores false positives
- **Handles**:
  - JSON encoding differences (normalizes before comparing)
  - Floating point precision (`45.99502182` vs `45.995021820068`)
  - Whitespace differences
- **Suppresses**: Logs for JSON-only changes (encoding normalization)

### 5. Batched Logging ✅
- **Location**: `log()` and `flush_logs()` lines ~1850-1882
- **What**: Batches log entries to avoid database thrashing
- **Why**: Writing 5000 individual logs = 5000 DB writes = slow
- **Batch Size**: 5 entries (flushes frequently for real-time visibility)

### 6. Live Progress UI ✅
- **Location**: JavaScript `updateProgressUI()` lines ~1015-1189
- **What**: Parses logs and updates progress dashboard in real-time
- **Updates**: Every 2 seconds via AJAX polling
- **Shows**:
  - Current phase (Fetch, Phase 1, Phase 2, Complete)
  - Hotels processed count
  - Created/Updated/Images stats
  - Progress bar with percentage
  - Time elapsed and estimated remaining

### 7. Batched Background Processing ✅
- **Location**: `process_single_batch()` lines ~1619-1827
- **What**: Processes imports in small batches via WP Cron (no timeouts)
- **Phases**:
  - **Fetch**: 200 hotels per batch from API
  - **Phase 1**: 200 hotels per batch (create posts, no images)
  - **Phase 2**: 50 hotels per batch (download images)
  - **Finalize**: Draft removed hotels
- **Why**: Each batch < 60s, works on WP Engine with strict timeouts

### 8. Draft Removed Hotels ✅
- **Location**: `draft_removed_hotel()` lines ~1850-1870
- **What**: Sets hotels to draft if they're removed from API
- **How**: Compares WordPress hotel IDs vs API hotel IDs, drafts the diff

### 9. Malformed HTML Fix ✅
- **Location**: `single-hotel.php` lines ~340-353
- **What**: Fixes unclosed/malformed HTML tags in API descriptions
- **Why**: API sends broken HTML that makes buttons clickable
- **Uses**: DOMDocument to auto-close tags

### 10. Update Detection for Images ✅
- **Location**: `process_hotel()` line ~2111
- **What**: Processes images for BOTH new AND existing hotels
- **Why**: Detects if API added new images to existing hotel

## Data Storage

### Custom Post Type: `hotel`

**Post Fields**:
- `post_title`: Hotel name (from API `name` or `businessName`)
- `post_name`: Slug (from API `slug` or auto-generated)
- `post_content`: Description (German text from API)
- `post_status`: `publish` or `draft`

**Meta Fields** (~40 fields):
- Basic: `hotel_id`, `ref_code`, `api_slug`
- Address: `business_address_1-4`, `business_zip`, `business_city`, `business_country`
- Location: `location_latitude`, `location_longitude`, `distance_to_airport/train`
- Capacity: `max_capacity_rooms`, `max_capacity_people`, `rooms`, `capacity`
- Texts: `description`, `arrival_car/flight/train`
- JSON Data: `texts_json`, `meeting_rooms`, `cancellation_rules`, `medias_json`, `attributes`
- Finder URLs: `finder_url_slug`, `finder_url_refcode`, `finder_add_slug`, `finder_add_refcode`

**Attachments**:
- Stored in WordPress Media Library
- Meta: `_seminargo_source_url` (for deduplication)
- Gallery: `gallery` meta field contains array of attachment IDs
- Featured: First image set via `_thumbnail_id`

## API Integration

**Endpoint**: `https://lister-staging.seminargo.com/pricelist/graphql`

**Query**:
```graphql
{
  hotelList(skip: X, limit: 200) {
    id, slug, refCode, name, businessName
    locationLatitude, locationLongitude
    medias { id, name, url, previewUrl, mimeType }
    meetingRooms { id, name, area, capacity... }
    texts { id, type, language, details }
    attributes { attribute, values }
    cancellationRules { id, sequence, daysToEvent... }
  }
}
```

## Performance Optimizations

1. **Batched API Fetching**: 200 hotels per request (not all 4815 at once)
2. **Batched Processing**: Processes in chunks via WP Cron (no timeouts)
3. **Batched Logging**: Writes logs in groups of 5 (reduces DB load)
4. **Image Deduplication**: Checks if image exists by URL before downloading
5. **Separate Phases**: Hotels first (fast), then images (slow) - hotels are live immediately
6. **Smart Comparisons**: Only updates if data actually changed (not just encoding)

## WP Engine Compatibility

### Constraints
- **AJAX Timeout**: 60-120 seconds (cannot be overridden)
- **Memory**: 512MB
- **Execution Time**: Can be extended via `ini_set()` but gateway timeout still applies

### Solutions
- **Batched Processing**: Each batch < 60s (no gateway timeout)
- **WP Cron**: Background jobs bypass AJAX timeout
- **Progress Polling**: Frontend polls for updates instead of waiting for response

## Common Issues & Solutions

### Issue: "Updated hotel" spam with identical values
**Cause**: JSON encoding normalization (one-time migration)
**Fix**: Suppress logs for JSON-only changes (line ~2526)
**Status**: ✅ Fixed

### Issue: Image download fails with `%C3_` in URL
**Cause**: API sends broken UTF-8 encoding (`%C3_` should be `%C3%9F`)
**Fix**: `encode_image_url()` detects and fixes broken patterns (line ~2874-2887)
**Status**: ✅ Fixed

### Issue: Frontend timeout on WP Engine
**Cause**: Import takes > 60s, gateway kills connection
**Fix**: Batched background processing via WP Cron (line ~1619-1827)
**Status**: ✅ Fixed

### Issue: Images not showing in Media Library
**Cause**: Files saved without extensions (UUID names)
**Fix**: Detect MIME type, add proper extension (lines ~2372-2412)
**Status**: ✅ Fixed

### Issue: "Sorry, you are not allowed to upload this file type"
**Cause**: WordPress upload security blocking images
**Fix**: Bypass `media_handle_sideload()`, use direct `wp_insert_attachment()` (line ~2383)
**Status**: ✅ Fixed

### Issue: Unclosed `<a>` tags make buttons clickable
**Cause**: API sends malformed HTML in descriptions
**Fix**: DOMDocument auto-closes tags before rendering (single-hotel.php line ~344-353)
**Status**: ✅ Fixed

## Critical Code Sections (DO NOT MODIFY WITHOUT UNDERSTANDING)

### 1. URL Encoding Function (lines ~2856-2892)
Handles broken UTF-8 sequences from API. Complex regex patterns to fix `%C3_` corruption.

### 2. Image Processing (lines ~2330-2424)
Direct file operations bypassing WordPress security. Careful with file permissions.

### 3. Batched Import (lines ~1619-1827)
Complex state machine with phases. Progress stored in options table.

### 4. Smart Comparison (lines ~2538-2573)
Normalizes data before comparing. Critical for preventing false updates.

### 5. Logging System (lines ~1850-1895)
Batches writes to avoid DB thrashing. Must flush at strategic points.

## Testing Checklist

Before deploying changes:

- [ ] Test on local (no timeout limits)
- [ ] Test initial import (all hotels + images)
- [ ] Test re-import (should show 0 updates if API unchanged)
- [ ] Check logs don't spam (no JSON encoding noise)
- [ ] Verify images download with correct extensions
- [ ] Check Media Library shows all images
- [ ] Verify featured images display on frontend
- [ ] Test on WP Engine (strict timeout limits)
- [ ] Verify batched import completes without timeout
- [ ] Check malformed HTML doesn't break frontend
- [ ] Verify finder URLs are correct

## Version History

- **af6b404**: Last stable version before major refactoring (batched fetch, working images)
- **Current**: Batched background processing, WP Engine compatible, smart change detection

## Emergency Rollback

If import is completely broken, revert to working version:

```bash
git show af6b404:inc/hotel-importer.php > inc/hotel-importer.php
# Then manually change API endpoint to staging (line 16)
```

## Developer Notes

- **Don't disable image processing** - User needs images, always
- **Don't use single API call** for 4815 hotels - Too much memory, will timeout
- **Don't log JSON encoding changes** - It's just normalization spam
- **Don't break the URL encoder** - It handles broken API data, complex patterns
- **Always use batched logging** - Direct writes cause DB thrashing
- **Test on WP Engine** - Strictest environment, if it works there it works everywhere
