# Instructions for Creating Pull Request

## Modified Files:
1. `members/include/admin/managemenu/_functions.php`
2. `plugins/donations/include/menu_module.php`
3. `plugins/donations/classes/campaign.php`

## Steps to Create Pull Request:

### Option 1: Manual Copy (Safest for Privacy)
1. Download a fresh copy of BlueThrust CMS to your local computer
2. Copy these 3 modified files from your server to the fresh copy
3. Use Git on your local computer to commit and push to your fork
4. Create pull request from your fork

### Option 2: Using Git Locally
1. Clone the official BlueThrust repository (or your fork) to your local computer
2. Copy only the 3 modified files from S:\ to your local repo
3. Commit with this message:

```
Fix: Menu system bug where $menuItemObj points to wrong menu item

- Fixed saveMenuItem() to query database for correct menu item ID
- Fixed savePoll() with same approach  
- Fixed donation campaign menu items to save correctly
- Added prefix support for donation menu items
- Works on fresh installs without repair scripts

Resolves issue where creating/editing menu items would update wrong records
```

4. Push to your fork
5. Create pull request on GitHub

### What NOT to include:
- Any test/diagnostic PHP files (already deleted)
- Debug logs
- Server-specific paths or configurations
- Database credentials
- Any references to Squadbase

## Files Summary:
All changes are in standard BlueThrust core files - no custom code or site-specific modifications.
