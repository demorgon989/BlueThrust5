<?php
require '_config.php';

$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname, 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$prefix = $dbprefix;

echo "<h2>Fixing Home Menu Item</h2>";

// Fix Home menu item to point to correct link record
$result = $conn->query("UPDATE {$prefix}menu_item SET itemtype_id = 1 WHERE menuitem_id = 1");
if ($result) {
    echo "<p style='color:green;'>✅ Fixed Home menu item - now points to link record 1</p>";
} else {
    echo "<p style='color:red;'>❌ Failed to update Home menu item</p>";
}

// Verify
$result = $conn->query("SELECT menuitem_id, name, itemtype_id FROM {$prefix}menu_item WHERE menuitem_id = 1");
$row = $result->fetch_assoc();
echo "<p>Home menu item now has itemtype_id = ".$row['itemtype_id']."</p>";

echo "<h2>Adding Prefix to Donate Menu Item</h2>";

// Check if Donate is a donation type (it should be)
$result = $conn->query("SELECT menuitem_id, name, itemtype, itemtype_id FROM {$prefix}menu_item WHERE name LIKE '%Donate%'");
$donateItem = $result->fetch_assoc();

if ($donateItem) {
    echo "<p>Found: Menu Item ID ".$donateItem['menuitem_id']." - '".$donateItem['name']."' - Type: ".$donateItem['itemtype']."</p>";
    
    if ($donateItem['itemtype'] == 'donation') {
        echo "<p style='color:orange;'>ℹ️ Donate is a 'donation' type menu item, not a 'link' type.</p>";
        echo "<p>The prefix is stored in the menu_item table itself for donation types, not in menuitem_link.</p>";
        
        // Check if there's a prefix column in menu_item table
        $result = $conn->query("SHOW COLUMNS FROM {$prefix}menu_item LIKE 'prefix'");
        if ($result->num_rows > 0) {
            echo "<p style='color:green;'>✅ Prefix column exists in menu_item table</p>";
            $conn->query("UPDATE {$prefix}menu_item SET prefix = '<b>&middot;</b>' WHERE menuitem_id = ".$donateItem['menuitem_id']);
            echo "<p style='color:green;'>✅ Added prefix to Donate menu item</p>";
        } else {
            echo "<p style='color:red;'>❌ No prefix column in menu_item table - prefix only works for link-type items</p>";
            echo "<p><strong>Solution:</strong> The donation menu item display function needs to manually add the prefix in the output.</p>";
        }
    }
}

echo "<h2>Verification</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-family:monospace; font-size:11px;'>";
echo "<tr style='background:#ddd;'><th>MenuItemID</th><th>Name</th><th>Type</th><th>TypeID</th><th>Status</th></tr>";

$result = $conn->query("SELECT mi.menuitem_id, mi.name, mi.itemtype, mi.itemtype_id, ml.menulink_id, ml.prefix
                        FROM {$prefix}menu_item mi
                        LEFT JOIN {$prefix}menuitem_link ml ON mi.itemtype_id = ml.menulink_id
                        WHERE mi.menuitem_id IN (1, ".$donateItem['menuitem_id'].")
                        ORDER BY mi.menuitem_id");

while ($row = $result->fetch_assoc()) {
    $status = "✅ OK";
    if ($row['itemtype'] == 'link' && $row['itemtype_id'] != $row['menulink_id']) {
        $status = "❌ MISMATCH";
    }
    
    echo "<tr>";
    echo "<td>".$row['menuitem_id']."</td>";
    echo "<td>".htmlspecialchars($row['name'])."</td>";
    echo "<td>".$row['itemtype']."</td>";
    echo "<td>".$row['itemtype_id']."</td>";
    echo "<td>".$status."</td>";
    echo "</tr>";
}

echo "</table>";

$conn->close();

echo "<p><strong>Refresh your site to see the changes!</strong></p>";
