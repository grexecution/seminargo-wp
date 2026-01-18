# Hotel Slug & Redirect System

**Documentation of how slugs are fetched, stored, and redirected**

---

## 📋 **Overview**

Your system fetches **TWO slugs** from the API and implements automatic 301 redirects from old URLs to new URLs.

---

## 🔍 **What the API Provides**

### **GraphQL Query** (inc/hotel-importer.php:5382-5383)
```graphql
hotelList(skip: X, limit: Y) {
    slug        # Current/new slug
    migSlug     # Old slug from migration
    # ... other fields
}
```

**Two slug fields:**
1. **`slug`** - The **current, active slug** for the hotel
2. **`migSlug`** - The **old slug** from a previous system/migration

---

## 💾 **How Slugs Are Stored in WordPress**

### **WordPress Post Table** (post_name)
**Location:** `wp_posts.post_name`
**Value:** Sanitized version of current API `slug`
**Code:** inc/hotel-importer.php:3957, 4020
```php
// Use API slug as WordPress post slug (sanitized)
$wp_slug = !empty($hotel->slug) ? sanitize_title($hotel->slug) : sanitize_title($hotel_title);

// Stored as:
'post_name' => $wp_slug
```

**URL Generated:** `https://yoursite.com/hotel/{post_name}/`

---

### **Post Meta Fields**

**1. `api_slug`** (inc/hotel-importer.php:4063)
- **Value:** Current slug from API (raw, unsanitized)
- **Purpose:** Reference to exact API slug
- **Example:** `"hotel-zum-goldenen-lowen-gottingen"`

**2. `mig_slug`** (inc/hotel-importer.php:4064)
- **Value:** Old slug from API `migSlug` field
- **Purpose:** Handle redirects from old URLs
- **Example:** `"old-hotel-name"` or `"migration-slug-123"`

**3. `finder_url_slug`** (inc/hotel-importer.php:4065)
- **Value:** `https://lister.seminargo.com/?showHotelBySlug={slug}`
- **Purpose:** Direct link to hotel in finder system

**4. `finder_url_refcode`** (inc/hotel-importer.php:4066)
- **Value:** `https://lister.seminargo.com/?showHotelByRefCode={refCode}`
- **Purpose:** Alternative finder link using ref code

---

## 🔄 **Redirect Logic**

### **Function:** `seminargo_redirect_old_hotel_slugs()`
**Location:** functions.php:2575-2640
**Hook:** `template_redirect` (priority 1)
**Runs:** Frontend only (not admin, AJAX, or cron)

### **How It Works:**

```php
1. User visits: https://yoursite.com/hotel/old-hotel-name/
                                              ↓
2. Function extracts slug from URL: "old-hotel-name"
                                              ↓
3. Queries database for hotel with mig_slug = "old-hotel-name"
                                              ↓
4. If found, gets new permalink: https://yoursite.com/hotel/new-hotel-name/
                                              ↓
5. 301 Redirect to new URL (preserves query strings)
                                              ↓
6. User sees new URL, search engines update
```

### **Code Logic** (functions.php:2575-2640):

```php
// 1. Extract current slug from URL
$request_uri = trim($_SERVER['REQUEST_URI'], '/');
$uri_parts = explode('/', $request_uri);
$current_slug = end($uri_parts); // e.g., "old-hotel-name"

// 2. Find hotel by old slug (mig_slug meta)
$hotel_query = new WP_Query([
    'post_type' => 'hotel',
    'meta_query' => [
        [
            'key' => 'mig_slug',
            'value' => $current_slug,
            'compare' => '=',
        ],
    ],
]);

// 3. If found, redirect to new permalink
if ($hotel_query->have_posts()) {
    $hotel_id = $hotel_query->posts[0];
    $new_url = get_permalink($hotel_id); // Uses post_name

    // 301 Permanent Redirect
    wp_redirect($new_url, 301);
    exit;
}
```

---

## 📊 **Example Flow**

### **Scenario: Hotel Slug Changes in API**

**Initial State:**
- API: `slug = "hotel-goldener-lowe"`
- API: `migSlug = null`
- WordPress: `post_name = "hotel-goldener-lowe"`
- WordPress Meta: `api_slug = "hotel-goldener-lowe"`, `mig_slug = ""`

**URL:** `https://yoursite.com/hotel/hotel-goldener-lowe/`

---

**API Updates (slug changes):**
- API: `slug = "hotel-goldener-loewe"` (spelling fixed)
- API: `migSlug = "hotel-goldener-lowe"` (old slug preserved)

**After Sync:**
- WordPress: `post_name = "hotel-goldener-loewe"` (updated)
- WordPress Meta: `api_slug = "hotel-goldener-loewe"`, `mig_slug = "hotel-goldener-lowe"`

**New URL:** `https://yoursite.com/hotel/hotel-goldener-loewe/`

---

**Redirect Behavior:**
```
User visits old URL:
https://yoursite.com/hotel/hotel-goldener-lowe/
                                    ↓
                        Extract slug: "hotel-goldener-lowe"
                                    ↓
                    Find hotel with mig_slug = "hotel-goldener-lowe"
                                    ↓
                            Found! Get new permalink
                                    ↓
                301 Redirect to: /hotel/hotel-goldener-loewe/
                                    ↓
                            User sees new URL
```

---

## ✅ **What's Good About This System**

1. **SEO-Friendly** - 301 redirects preserve search ranking
2. **Automatic** - No manual redirect management needed
3. **Preserves Links** - Old bookmarks/links still work
4. **API-Driven** - API controls the migration, not WordPress
5. **Query Strings Preserved** - `?param=value` carried over
6. **Prevents 404s** - Old URLs don't break

---

## ⚠️ **Limitations & Considerations**

### **1. Only Handles One Level of Migration**
- **Works:** API slug change (old → new)
- **Doesn't track:** WordPress slug changes if API slug stays the same

**Example Issue:**
```
1. API: slug = "hotel-a" → WP: post_name = "hotel-a"
2. Admin manually changes WP slug to "hotel-b" → WP: post_name = "hotel-b"
3. Next sync: API: slug = "hotel-a" → WP: post_name = "hotel-a" (OVERWRITES manual change)
4. No redirect from "hotel-b" to "hotel-a" (manual change lost)
```

**Why:** System assumes API is source of truth, doesn't track WordPress-side slug changes.

---

### **2. migSlug Must Be Set by API**
- WordPress doesn't auto-populate `migSlug` when slugs change
- API must provide `migSlug` value
- If API doesn't send `migSlug`, no redirect setup happens

**Check:**
```php
// In hotel-importer.php:4064
'mig_slug' => $hotel->migSlug ?? '',
```

If `$hotel->migSlug` is null/empty from API, no redirect is set up.

---

### **3. Performance Impact**
- Redirect function runs on **every frontend page load**
- Includes database query on 404s and non-hotel pages
- Could impact performance on high-traffic sites

**Optimization Possible:**
- Only run if `is_404()` or URL contains `/hotel/`
- Cache negative results (slug not found)
- Use direct SQL instead of WP_Query for speed

---

### **4. Multiple Old Slugs Not Supported**
- System only stores **ONE** old slug (`migSlug`)
- If hotel has changed slugs multiple times, only latest old slug redirects
- Very old slugs would 404

**Example:**
```
History: slug1 → slug2 → slug3 (current)

Redirect works: slug2 → slug3 ✅
Redirect fails: slug1 → slug3 ❌ (no tracking of slug1)
```

---

## 🔧 **How to Verify It's Working**

### **Test 1: Check if migSlug is Being Fetched**

```bash
# Check a few hotels to see if mig_slug is populated
wp post meta get POST_ID mig_slug
wp post meta get POST_ID api_slug
```

**Expected Results:**
- `api_slug` - Should have value (current slug)
- `mig_slug` - May be empty if hotel never changed slug, or has old slug value

---

### **Test 2: Manually Test a Redirect**

If you find a hotel with both slugs populated:

```bash
# Find a hotel with mig_slug
wp post meta list POST_ID | grep slug

# Example output:
# api_slug: hotel-new-name
# mig_slug: hotel-old-name
```

Then visit: `https://yoursite.com/hotel/hotel-old-name/`

**Should:**
- 301 redirect to: `https://yoursite.com/hotel/hotel-new-name/`
- Browser URL updates automatically
- No 404 error

---

### **Test 3: Check API Response**

See if API actually provides `migSlug`:

```bash
# Make test API call
curl -X POST https://lister.seminargo.com/pricelist/graphql \
  -H "Content-Type: application/json" \
  -d '{"query": "{hotelList(skip: 0, limit: 5) { id, slug, migSlug }}"}' \
  | jq '.data.hotelList[] | {slug, migSlug}'
```

**Check:**
- Do hotels have `migSlug` values?
- Are they different from `slug`?
- Or are they all `null`/empty?

---

## 💡 **Recommendations**

### **If API Provides migSlug:** ✅ System works perfectly
- Old URLs redirect automatically
- No action needed

### **If API Doesn't Provide migSlug:** ⚠️ Need Custom Logic

You'd need to track slug changes WordPress-side:

```php
// Before updating post_name, store old slug
$old_slug = $post->post_name;
$new_slug = $wp_slug;

if ($old_slug !== $new_slug) {
    // Store old slug for redirect
    update_post_meta($post_id, 'old_slugs', [$old_slug, ...]);
    // Or add to mig_slug if empty
    if (empty(get_post_meta($post_id, 'mig_slug', true))) {
        update_post_meta($post_id, 'mig_slug', $old_slug);
    }
}
```

---

## 🎯 **Summary**

### **Current System:**
1. ✅ Fetches `slug` (current) and `migSlug` (old) from API
2. ✅ Stores both in WordPress meta
3. ✅ Uses `slug` for WordPress permalink (post_name)
4. ✅ 301 redirects `migSlug` URLs to new `slug` URLs
5. ✅ Runs on every frontend request
6. ✅ Preserves query strings
7. ⚠️ Relies on API providing `migSlug`
8. ⚠️ Only handles one previous slug (not multiple)
9. ⚠️ Overwrites manual WordPress slug changes

### **Where It's Implemented:**
- **Fetch:** inc/hotel-importer.php:5382-5383
- **Store:** inc/hotel-importer.php:4063-4064
- **Update:** inc/hotel-importer.php:3957, 4002-4005, 4020
- **Redirect:** functions.php:2575-2640

---

**Want me to check if your API actually provides migSlug values?** Or optimize the redirect performance?
