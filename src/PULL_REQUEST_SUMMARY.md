# BlueThrust Menu System Fix - Pull Request Summary

## Problem Description

The BlueThrust CMS form framework has a critical bug in the menu item creation/editing system where the `$menuItemObj` object points to the wrong menu item during `afterSave` callbacks. This causes menu items to update incorrect records in the database, resulting in:

- Menu links pointing to wrong URLs
- Donation campaign menu items not saving their campaign associations
- Newly created menu items updating existing menu items (often ID 1 - "Home")

### Root Cause

In `members/include/admin/managemenu/_functions.php`, the `saveMenuItem()` function uses:
```php
$saveAdditional = ["menuitem_id" => $menuItemObj->get_info("menuitem_id")];
```

However, during `afterSave` callbacks, `$menuItemObj` has not been updated with the newly created menu item's ID and points to a previously loaded menu item (often ID 1).

## Solution

Instead of trusting `$menuItemObj`, we query the database directly to find the correct menu item by matching:
1. Item name (`itemname`)
2. Menu category (`menucategory_id`)
3. Item type (`itemtype`)

This ensures we always update the correct menu item record.

## Files Modified

### Core System Files

#### 1. `members/include/admin/managemenu/_functions.php`
**Changes:**
- Modified `saveMenuItem()` function to query database for actual menu item ID
- Modified `savePoll()` function with same fix
- Added fallback to old behavior if query fails (backward compatibility)
- Now uses prepared statements for the UPDATE query

**Impact:** Fixes menu item creation/editing for ALL item types (links, images, custom pages, polls, etc.)

### Plugin Files

#### 2. `plugins/donations/include/menu_module.php`
**Changes:**
- Modified `saveDonationMenuItem()` to query database for actual menu item ID
- Added comprehensive admin logging to `plugins/donations/debug.log`
- Disabled public debug output (production-ready)
- Uses prepared statements for all database queries

**Impact:** Fixes donation campaign menu item creation/editing

#### 3. `plugins/donations/classes/campaign.php`
**Changes:**
- Added query guards to prevent SQL errors when campaign ID is 0 or invalid
- Added duplicate filtering in `populateDonationInfo()` and `getDonators()`
- Made queries MySQL strict mode compliant
- Added debug logging for troubleshooting

**Impact:** Prevents crashes when invalid campaign IDs are encountered

## Testing

### Test on Fresh Install
1. Create a new menu item (any type) - should work correctly
2. Edit an existing menu item - should update the correct item
3. Create a donation campaign menu item - should link to correct campaign
4. All menu links should point to their correct URLs

### Test on Existing Install
1. Run `test_menu_fix.php` to verify menu item integrity
2. If issues found, run `fix_menu.php` to repair existing menu items
3. Create/edit menu items to verify fix is working going forward

### Verification Script
We've included `test_menu_fix.php` which checks:
- All link menu items are correctly linked to their URL records
- All donation menu items are correctly linked to their campaigns
- Identifies orphaned link records
- Provides status report of menu system health

## Backward Compatibility

✅ **Fully backward compatible**
- Fallback to old behavior if database query fails
- No database schema changes required
- Existing menu items continue to work (may need one-time repair via `fix_menu.php`)

## Additional Helper Scripts (Optional)

These scripts are not part of the core fix but help with troubleshooting and repair:

- **`diagnose_menu.php`** - Shows all menu items and their relationships
- **`fix_menu.php`** - One-time repair script for existing broken menu items
- **`test_menu_fix.php`** - Verification script to check fix is working

## Deployment Instructions

### For New Sites
1. Apply these file changes before launching
2. Menu system will work correctly out of the box

### For Existing Sites
1. Apply these file changes
2. Run `fix_menu.php` once to repair existing menu items
3. Delete helper scripts after verification (optional)
4. New menu item operations will work correctly going forward

## Code Review Notes

### Security
- All database queries use prepared statements
- Input sanitization via `real_escape_string()` and type casting
- No SQL injection vulnerabilities introduced

### Performance
- Adds one additional SELECT query per menu item save operation
- Query is indexed (searches on name + category + type)
- Minimal performance impact

### Maintainability
- Well-commented code explaining the fix
- Fallback behavior for edge cases
- Debug logging for troubleshooting

## Summary

This fix resolves a fundamental framework bug that has been causing menu item creation/editing issues across all menu item types. The solution is elegant, backward-compatible, and production-ready.

**Total Impact:**
- ✅ All menu item types now save correctly
- ✅ No more wrong menu items being updated
- ✅ Donation campaign menu items work properly
- ✅ Backward compatible with existing installations
- ✅ Security-conscious implementation

---

**Files to Commit:**
1. `members/include/admin/managemenu/_functions.php`
2. `plugins/donations/include/menu_module.php`
3. `plugins/donations/classes/campaign.php`

**Optional Helper Scripts (for documentation/support):**
- `diagnose_menu.php`
- `fix_menu.php`
- `test_menu_fix.php`
