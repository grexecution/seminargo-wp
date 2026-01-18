# migSlug Fix - Version 1.7.6

**Issue:** Old URLs with special characters (ä, ö, ü) not redirecting
**Example:** `https://www.seminargo.com/hotel/Jugendg%C3%A4stehaus_Bad_Ischl`

---

## 🐛 **THE BUG - Two Issues Found**

### **Issue #1: migSlug Not Being Fetched** ❌

**Problem:**
- Batched import query was **missing `migSlug`** field
- File: inc/hotel-importer.php:5383
- API has the data, but WordPress wasn't requesting it

**The Query Was:**
```graphql
hotelList(skip: X, limit: Y) {
    id
    slug          ✅ Had this
    # migSlug    ❌ MISSING THIS!
    refCode
    # ...
}
```

**Result:**
- `$hotel->migSlug` was always undefined/null
- Saved as: `mig_slug = ''` (empty)
- Redirect had no old slug to match

**✅ FIXED in v1.7.5:**
Added `migSlug` to the GraphQL query (line 5383)

---

### **Issue #2: URL Encoding Mismatch** ❌

**Problem:**
Old URLs have special characters that appear URL-encoded in browsers:

```
Old URL:    Jugendg%C3%A4stehaus_Bad_Ischl  (browser displays with %)
Decoded:    Jugendgästehaus_Bad_Ischl       (actual characters)
```

**The Mismatch:**
- **Old system:** Slugs with capitals, underscores, special chars
  - Example: `Jugendgästehaus_Bad_Ischl`
- **New system:** Sanitized slugs (lowercase, hyphens, no special chars)
  - Example: `jugendgastehaus-bad-ischl`

**Why Redirect Failed:**
1. User visits: `/hotel/Jugendg%C3%A4stehaus_Bad_Ischl`
2. PHP decodes to: `Jugendgästehaus_Bad_Ischl`
3. Compares with `mig_slug = "Jugendgästehaus_Bad_Ischl"`
4. Should match, BUT might fail due to:
   - Case sensitivity (capital J vs j)
   - Underscore vs hyphen (Bad_Ischl vs bad-ischl)
   - WordPress meta query exact matching

**✅ FIXED in v1.7.6:**
Stores **3 versions** of migSlug and checks **6 variations** in redirect

---

## ✅ **THE FIX - How It Works Now**

### **1. Storage (inc/hotel-importer.php:4060-4072)**

When syncing, now saves **3 versions** of the old slug:

```php
// Example: API provides migSlug = "Jugendgästehaus_Bad_Ischl"

mig_slug              = "Jugendgästehaus_Bad_Ischl"    // Raw (capitals, underscores, umlauts)
mig_slug_sanitized    = "jugendgastehaus-bad-ischl"    // Sanitized (WP standard)
mig_slug_lowercase    = "jugendgästehaus_bad_ischl"    // Lowercase only
```

**Why 3 versions?**
- Handles different old URL formats
- Sanitized version matches WordPress standard slugs
- Lowercase handles case-insensitive matching
- Raw preserves API value for reference

---

### **2. Redirect Matching (functions.php:2606-2650)**

When checking for redirects, now tries **6 variations** of the incoming slug:

```php
// User visits: /hotel/Jugendg%C3%A4stehaus_Bad_Ischl
// PHP decodes to: Jugendgästehaus_Bad_Ischl

// Generate variations:
1. Jugendgästehaus_Bad_Ischl           // Original (decoded)
2. Jugendgästehaus_Bad_Ischl           // Double-decode (same if already decoded)
3. jugendgastehaus-bad-ischl           // WordPress sanitized
4. jugendgästehaus_bad_ischl           // Lowercase
5. Jugendgästehaus-Bad-Ischl           // Underscores → hyphens
6. jugendgästehaus-bad-ischl           // Both lowercase + hyphens
```

Then checks if ANY variation matches ANY of the 3 stored versions:

```sql
WHERE (
    mig_slug IN (variation1, variation2, ..., variation6)
    OR
    mig_slug_sanitized IN (variation1, variation2, ..., variation6)
    OR
    mig_slug_lowercase IN (variation1, variation2, ..., variation6)
)
```

**Total Comparisons:** 6 variations × 3 storage formats = **18 possible matches**

**This handles:**
- ✅ URL-encoded characters (ä, ö, ü)
- ✅ Case differences (Capital vs lowercase)
- ✅ Separator differences (underscore vs hyphen)
- ✅ Sanitized vs unsanitized
- ✅ All combinations

---

## 🧪 **Testing the Fix**

### **Step 1: Re-Sync Hotel ID 10594**

To populate the migSlug field:

**Option A: Sync Just That Hotel** (Quick test)
```
1. Go to admin → Hotels → Import / Sync
2. Click "Fetch Now"
3. Wait 1-2 minutes (syncs ~400 hotels)
4. Click "STOP" once hotel 10594 is likely synced
```

**Option B: Full Sync** (Gets all hotels)
```bash
wp seminargo import-hotels --all
```

---

### **Step 2: Verify migSlug is Now Saved**

Find the WordPress post for hotel ID 10594:

```bash
# Get post ID
wp post list --post_type=hotel --meta_key=hotel_id --meta_value=10594 --format=ids

# Check all slug fields (replace POST_ID with actual ID)
wp post meta list POST_ID | grep slug
```

**Expected Output:**
```
api_slug: schloss-luberegg
mig_slug: Schloss_Luberegg                    // Raw from API
mig_slug_sanitized: schloss-luberegg          // Sanitized
mig_slug_lowercase: schloss_luberegg          // Lowercase only
```

---

### **Step 3: Test the Redirect**

Once migSlug is populated, test old URL:

**Old URL (from your example):**
```
https://www.seminargo.com/hotel/Jugendg%C3%A4stehaus_Bad_Ischl
```

**What Should Happen:**
1. Browser visits URL with `%C3%A4` encoding
2. Server decodes to `Jugendgästehaus_Bad_Ischl`
3. Redirect function tries all variations
4. Finds match in one of the 3 mig_slug fields
5. Gets new permalink (current slug)
6. **301 redirects** to new URL
7. Browser URL changes

**Verify:**
- ✅ No 404 error
- ✅ Browser URL updates to new slug
- ✅ Content loads correctly
- ✅ Browser console shows 301 redirect

---

## 📊 **Example Matching Scenarios**

### **Scenario 1: Capitals + Underscores**
```
Old URL:   /hotel/Schloss_Luberegg
Stored:    mig_slug_lowercase = "schloss_luberegg"
Match:     Variation #4 (lowercase) matches stored lowercase ✅
```

### **Scenario 2: URL-Encoded Umlauts**
```
Old URL:   /hotel/Jugendg%C3%A4stehaus_Bad_Ischl
Decoded:   Jugendgästehaus_Bad_Ischl
Stored:    mig_slug = "Jugendgästehaus_Bad_Ischl"
Match:     Variation #1 (original decoded) matches raw storage ✅
```

### **Scenario 3: Sanitized Version**
```
Old URL:   /hotel/jugendgastehaus-bad-ischl
Stored:    mig_slug_sanitized = "jugendgastehaus-bad-ischl"
Match:     Variation #3 (sanitized) matches sanitized storage ✅
```

### **Scenario 4: Mixed Case + Hyphens**
```
Old URL:   /hotel/Jugendgastehaus-Bad-Ischl
Variation: jugendgastehaus-bad-ischl (#6: lowercase + hyphens)
Stored:    mig_slug_sanitized = "jugendgastehaus-bad-ischl"
Match:     ✅
```

---

## ⚠️ **Important Notes**

### **1. Re-Sync Required**

The fix only applies to **future syncs**. Hotels synced before v1.7.6 won't have the migSlug fields populated.

**You must re-sync** to populate:
- `mig_slug`
- `mig_slug_sanitized`
- `mig_slug_lowercase`

### **2. Old URLs Still Work During Sync**

If you're doing an incremental sync (not re-processing all hotels), old URLs for un-synced hotels won't redirect yet. They'll start working as each hotel is re-synced.

### **3. Performance Impact**

The redirect now checks 18 possible combinations (6 variations × 3 fields). This is more database queries, but:
- ✅ Only runs on 404s or non-hotel pages
- ✅ Caches negative results
- ✅ Exits early on exact match
- ⚠️ Could optimize with direct SQL if needed

---

## 🎯 **Summary**

### **What Was Broken:**
1. ❌ Batched import missing `migSlug` from GraphQL query
2. ❌ No handling for URL-encoded characters
3. ❌ No handling for case/separator differences

### **What's Fixed in v1.7.6:**
1. ✅ Added `migSlug` to GraphQL query (now fetches from API)
2. ✅ Stores 3 versions of migSlug (raw, sanitized, lowercase)
3. ✅ Redirect checks 6 URL variations against 3 stored versions
4. ✅ Handles: URL-encoding, capitals, underscores, umlauts

### **How to Verify:**
1. Re-sync hotels (full or incremental)
2. Check mig_slug fields are populated
3. Test old URL: `https://www.seminargo.com/hotel/Jugendg%C3%A4stehaus_Bad_Ischl`
4. Should 301 redirect to new URL

---

## 📋 **Meta Fields Reference**

After sync, each hotel will have:

```
api_slug              - Current slug from API (raw)
mig_slug              - Old slug from API (raw, with umlauts/capitals/underscores)
mig_slug_sanitized    - Old slug sanitized (WordPress standard: lowercase, hyphens, no special chars)
mig_slug_lowercase    - Old slug lowercased only (keeps underscores/umlauts)
```

**Visible in Admin:**
- Edit hotel → Scroll to "API Information" meta box
- Shows: API Slug (Current), Old Slug (migSlug), etc.

---

**Version 1.7.6 - Ready to test after re-sync!**

Next: Re-sync hotels, then test if old URLs redirect properly.
