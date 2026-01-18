# migSlug - Exact Match System (v1.7.7)

**Simplified to store EXACTLY as old URLs appear**

---

## ✅ **THE FIX**

### **What You Wanted:**
Store migSlug **exactly** like this: `Wohlf%C3%BChlhotel_Goiserer_M%C3%BChle`

### **What I Changed:**

**Before v1.7.7:** Stored 3 versions (raw, sanitized, lowercase) - too complex ❌

**Now v1.7.7:** Stores **1 version** - URL-encoded to match old URLs **exactly** ✅

---

## 📊 **How It Works Now**

### **1. API Provides (example):**
```json
{
    "migSlug": "Wohlfühlhotel_Goiserer_Mühle"
}
```
*(with actual ü characters)*

---

### **2. WordPress Stores (inc/hotel-importer.php:4065):**

```php
$mig_slug_raw = $hotel->migSlug ?? '';  // "Wohlfühlhotel_Goiserer_Mühle"
$mig_slug_encoded = rawurlencode($mig_slug_raw);  // "Wohlf%C3%BChlhotel_Goiserer_M%C3%BChle"

// Saved to database:
mig_slug = "Wohlf%C3%BChlhotel_Goiserer_M%C3%BChle"
```

**Exactly matches your old URL format!**

---

### **3. Redirect Compares (functions.php:2612-2627):**

```php
// User visits: /hotel/Wohlf%C3%BChlhotel_Goiserer_M%C3%BChle
// PHP auto-decodes to: Wohlfühlhotel_Goiserer_Mühle

$current_slug = "Wohlfühlhotel_Goiserer_Mühle";  // Decoded
$current_slug_encoded = rawurlencode($current_slug);  // Re-encode
// Result: "Wohlf%C3%BChlhotel_Goiserer_M%C3%BChle"

// Query database:
WHERE mig_slug = "Wohlf%C3%BChlhotel_Goiserer_M%C3%BChle"

// EXACT MATCH! ✅
```

---

## 🔄 **Complete Flow Example**

### **Hotel: Jugendgästehaus Bad Ischl (hotel_id: 12345)**

**API Response:**
```json
{
    "id": 12345,
    "slug": "jugendgastehaus-bad-ischl",          // Current (new system)
    "migSlug": "Jugendgästehaus_Bad_Ischl"        // Old (from migration)
}
```

**WordPress Stores:**
```
Post:
  post_name = "jugendgastehaus-bad-ischl"        // Current slug (URL: /hotel/jugendgastehaus-bad-ischl/)

Meta:
  api_slug = "jugendgastehaus-bad-ischl"         // Current from API
  mig_slug = "Jugendg%C3%A4stehaus_Bad_Ischl"   // URL-encoded old slug
```

**Old URL:**
```
https://www.seminargo.com/hotel/Jugendg%C3%A4stehaus_Bad_Ischl
```

**Redirect Process:**
```
1. User visits: /hotel/Jugendg%C3%A4stehaus_Bad_Ischl
                    ↓
2. PHP decodes to: Jugendgästehaus_Bad_Ischl
                    ↓
3. Re-encode: Jugendg%C3%A4stehaus_Bad_Ischl
                    ↓
4. Query DB: WHERE mig_slug = "Jugendg%C3%A4stehaus_Bad_Ischl"
                    ↓
5. Found hotel #12345!
                    ↓
6. Get permalink: /hotel/jugendgastehaus-bad-ischl/
                    ↓
7. 301 Redirect
                    ↓
8. Browser shows: /hotel/jugendgastehaus-bad-ischl/
```

---

## ✅ **What's Stored in Database**

### **For Hotel ID 10594 (Schloss Luberegg):**

After sync with v1.7.7:

```
api_slug = "schloss-luberegg"                    // Current slug
mig_slug = "Schloss_Luberegg"                    // Old slug URL-encoded (if API provides)
```

**In Admin UI:**
- Edit Hotel → Scroll to "API Information" box
- **Old Slug (migSlug):** `Schloss_Luberegg`
- Shown **exactly** as stored

---

## 🧪 **How to Test**

### **Step 1: Re-Sync to Populate migSlug**

```bash
# Quick test (sync a batch)
Admin → Hotels → Import / Sync → Fetch Now → Wait 2min → STOP

# Or full sync
wp seminargo import-hotels --all
```

---

### **Step 2: Check Hotel 10594**

```bash
# Get WordPress post ID
wp post list --post_type=hotel --meta_key=hotel_id --meta_value=10594 --format=ids

# Check the mig_slug (replace 12345 with actual post ID)
wp post meta get 12345 mig_slug
```

**Should output:**
```
Schloss_Luberegg
```

*(Or whatever the API provides - URL-encoded format)*

---

### **Step 3: Test Old URL**

Visit the old URL in browser:
```
https://www.seminargo.com/hotel/Schloss_Luberegg
```

**Should:**
1. ✅ 301 redirect to new URL
2. ✅ Browser URL changes to new slug
3. ✅ Content loads
4. ✅ No 404 error

**Check in Browser DevTools:**
- F12 → Network tab
- Visit old URL
- See: Status `301 Moved Permanently`
- Location header shows new URL

---

## 📋 **Changes in v1.7.7**

### **Storage (inc/hotel-importer.php:4064-4071):**

**Removed:**
- ❌ `mig_slug_sanitized`
- ❌ `mig_slug_lowercase`
- ❌ Complex variations

**Now Stores:**
- ✅ `mig_slug` = `rawurlencode($hotel->migSlug)` - **ONE version, URL-encoded, exact**

---

### **Redirect (functions.php:2612-2627):**

**Removed:**
- ❌ 6 slug variations
- ❌ 3 meta field checks
- ❌ 18 possible comparisons

**Now Compares:**
- ✅ Incoming slug → URL-encode it
- ✅ Compare with `mig_slug` field
- ✅ **ONE comparison, exact match**

---

## 🎯 **Why This Is Better**

**Before (v1.7.6):**
- Stored 3 versions of slug
- Checked 6 variations
- 18 database comparisons
- Complex, confusing

**Now (v1.7.7):**
- Stores 1 version (URL-encoded)
- Checks 1 variation (re-encode incoming)
- 1 database comparison
- **Simple, exact, fast**

---

## 📝 **Technical Details**

### **rawurlencode() vs urlencode():**

Using `rawurlencode()` which is the **correct function for URL paths**:

```php
rawurlencode("Wohlfühlhotel") → "Wohlf%C3%BChlhotel"
urlencode("Wohlfühlhotel")    → "Wohlf%C3%BChlhotel" (same for this)
```

**But different for spaces:**
```php
rawurlencode("Hotel Name") → "Hotel%20Name"  ✅ Correct for URL paths
urlencode("Hotel Name")    → "Hotel+Name"    ❌ Wrong (for query strings)
```

So `rawurlencode()` is the right choice for slug matching.

---

## ⚠️ **What If API Sends Already-Encoded migSlug?**

**If API sends:** `"Wohlf%C3%BChlhotel"` (already encoded)

**Code will:** `rawurlencode("Wohlf%C3%BChlhotel")` → `"Wohlf%2525C3%2525BChlhotel"` (double-encoded!)

**This would be wrong!**

**How to check:**
```bash
# After sync, check one hotel
wp post meta get POST_ID mig_slug

# Should output:
Wohlf%C3%BChlhotel_...   ✅ Correct (single-encoded)

# NOT:
Wohlf%2525C3%2525BC...   ❌ Double-encoded
```

**If double-encoded, change code to:**
```php
'mig_slug' => $hotel->migSlug ?? '',  // Don't encode, use as-is
```

---

## ✅ **Summary**

**v1.7.7 Changes:**
- ✅ Stores migSlug **URL-encoded** (e.g., `Wohlf%C3%BChlhotel_Goiserer_M%C3%BChle`)
- ✅ Redirect **re-encodes** incoming slug to match
- ✅ **ONE storage field** (removed sanitized/lowercase versions)
- ✅ **Simple exact comparison**
- ✅ Matches old URL format **exactly**

**Example:**
```
Old URL:    /hotel/Wohlf%C3%BChlhotel_Goiserer_M%C3%BChle
Stored:     mig_slug = "Wohlf%C3%BChlhotel_Goiserer_M%C3%BChle"
Comparison: rawurlencode(decoded_url) === stored_mig_slug
Result:     EXACT MATCH ✅
```

**Next Steps:**
1. Re-sync hotels to populate mig_slug (URL-encoded format)
2. Test old URL redirects
3. Verify no double-encoding

---

**Version 1.7.7 - Simplified and Exact!**
