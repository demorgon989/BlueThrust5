<?php
/*
 * TEST SCRIPT: Verify Menu System Fix
 * 
 * This script helps verify that the menu item creation/editing fix is working correctly.
 * 
 * WHAT WAS FIXED:
 * - The BlueThrust form framework had a bug where $menuItemObj pointed to the wrong menu item
 *   during afterSave callbacks, causing menu items to update the wrong records.
 * 
 * - We fixed this by querying the database directly to find the correct menu item by:
 *   1. Item name
 *   2. Menu category
 *   3. Item type
 * 
 * FILES MODIFIED:
 * 1. members/include/admin/managemenu/_functions.php - Core menu system fix
 * 2. plugins/donations/include/menu_module.php - Donation plugin fix
 * 3. plugins/donations/classes/campaign.php - Query safety improvements
 */

require '_config.php';

$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname, 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$prefix = $dbprefix;

echo "<h1>Menu System Fix Verification</h1>";
echo "<p>This script checks that all menu items are correctly linked to their content.</p>";

// Check all link-type menu items
echo "<h2>Link Menu Items</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-family:monospace; font-size:12px;'>";
echo "<tr style='background:#ddd;'><th>Menu Item ID</th><th>Name</th><th>TypeID</th><th>Link Record</th><th>URL</th><th>Status</th></tr>";

$query = "
    SELECT mi.menuitem_id, mi.name, mi.itemtype_id, ml.menulink_id, ml.menuitem_id as link_menuitem, ml.link
    FROM {$prefix}menu_item mi
    LEFT JOIN {$prefix}menuitem_link ml ON mi.itemtype_id = ml.menulink_id
    WHERE mi.itemtype = 'link'
    ORDER BY mi.menucategory_id, mi.sortnum
";

$result = $conn->query($query);
$allCorrect = true;

while ($row = $result->fetch_assoc()) {
    $status = "✅ OK";
    $statusColor = "green";
    
    if ($row['itemtype_id'] == 0) {
        $status = "❌ No link assigned";
        $statusColor = "red";
        $allCorrect = false;
    } elseif ($row['menulink_id'] == null) {
        $status = "❌ Link record not found";
        $statusColor = "red";
        $allCorrect = false;
    } elseif ($row['link_menuitem'] != $row['menuitem_id']) {
        $status = "⚠️ Link belongs to different menu item";
        $statusColor = "orange";
        $allCorrect = false;
    }
    
    echo "<tr>";
    echo "<td>".$row['menuitem_id']."</td>";
    echo "<td>".htmlspecialchars($row['name'])."</td>";
    echo "<td>".$row['itemtype_id']."</td>";
    echo "<td>".($row['menulink_id'] ?? 'NULL')."</td>";
    echo "<td>".htmlspecialchars($row['link'] ?? 'N/A')."</td>";
    echo "<td style='color:".$statusColor.";'>".$status."</td>";
    echo "</tr>";
}

echo "</table>";

// Check donation menu items
echo "<h2>Donation Menu Items</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-family:monospace; font-size:12px;'>";
echo "<tr style='background:#ddd;'><th>Menu Item ID</th><th>Name</th><th>Campaign ID</th><th>Campaign Title</th><th>Status</th></tr>";

$query = "
    SELECT mi.menuitem_id, mi.name, mi.itemtype_id, dc.title
    FROM {$prefix}menu_item mi
    LEFT JOIN {$prefix}donations_campaign dc ON mi.itemtype_id = dc.donationcampaign_id
    WHERE mi.itemtype = 'donation'
    ORDER BY mi.menucategory_id, mi.sortnum
";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $status = "✅ OK";
        $statusColor = "green";
        
        if ($row['itemtype_id'] == 0) {
            $status = "❌ No campaign assigned";
            $statusColor = "red";
            $allCorrect = false;
        } elseif ($row['title'] == null) {
            $status = "❌ Campaign not found";
            $statusColor = "red";
            $allCorrect = false;
        }
        
        echo "<tr>";
        echo "<td>".$row['menuitem_id']."</td>";
        echo "<td>".htmlspecialchars($row['name'])."</td>";
        echo "<td>".$row['itemtype_id']."</td>";
        echo "<td>".htmlspecialchars($row['title'] ?? 'N/A')."</td>";
        echo "<td style='color:".$statusColor.";'>".$status."</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5' style='text-align:center; color:#666;'>No donation menu items found</td></tr>";
}

echo "</table>";

// Check for orphaned link records
echo "<h2>Orphaned Link Records</h2>";
echo "<p style='font-size:12px; color:#666;'>These are link records not connected to any menu item</p>";

$query = "
    SELECT ml.menulink_id, ml.menuitem_id, ml.link, mi.menuitem_id as actual_menuitem
    FROM {$prefix}menuitem_link ml
    LEFT JOIN {$prefix}menu_item mi ON ml.menuitem_id = mi.menuitem_id
    WHERE mi.menuitem_id IS NULL OR mi.itemtype_id != ml.menulink_id
";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-family:monospace; font-size:12px;'>";
    echo "<tr style='background:#ddd;'><th>Link ID</th><th>Claims to belong to Menu Item</th><th>Link URL</th><th>Action</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>".$row['menulink_id']."</td>";
        echo "<td>".$row['menuitem_id']."</td>";
        echo "<td>".htmlspecialchars($row['link'])."</td>";
        echo "<td><a href='?delete_link=".$row['menulink_id']."' style='color:red;'>Delete</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    $allCorrect = false;
} else {
    echo "<p style='color:green;'>✅ No orphaned link records found</p>";
}

// Handle deletion of orphaned links
if (isset($_GET['delete_link'])) {
    $linkID = (int)$_GET['delete_link'];
    $conn->query("DELETE FROM {$prefix}menuitem_link WHERE menulink_id = ".$linkID);
    echo "<p style='color:green;'>✅ Deleted orphaned link record ".$linkID."</p>";
    echo "<meta http-equiv='refresh' content='2'>";
}

// Final summary
echo "<hr>";
if ($allCorrect) {
    echo "<h2 style='color:green;'>✅ All menu items are correctly configured!</h2>";
    echo "<p>The menu system fix is working properly.</p>";
} else {
    echo "<h2 style='color:orange;'>⚠️ Some issues found</h2>";
    echo "<p>Run the <a href='fix_menu.php'>fix_menu.php</a> script to repair existing menu items, or manually fix them through the admin panel.</p>";
}

echo "<hr>";
echo "<h3>Files Modified for This Fix:</h3>";
echo "<ul style='font-family:monospace; font-size:12px;'>";
echo "<li><strong>members/include/admin/managemenu/_functions.php</strong> - Core menu system fix</li>";
echo "<li><strong>plugins/donations/include/menu_module.php</strong> - Donation menu item handler</li>";
echo "<li><strong>plugins/donations/classes/campaign.php</strong> - Query safety improvements</li>";
echo "</ul>";

$conn->close();
