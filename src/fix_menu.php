<?php
// Fix script to correct menu item links
require '_config.php';

$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname, 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$prefix = $dbprefix;

echo "<h2>Fixing Menu Items</h2>";

// Get all link-type menu items and their corresponding link records
$query = "
    SELECT mi.menuitem_id, mi.name, mi.itemtype_id as current_typeid, ml.menulink_id, ml.link
    FROM {$prefix}menu_item mi
    LEFT JOIN {$prefix}menuitem_link ml ON ml.menuitem_id = mi.menuitem_id
    WHERE mi.itemtype = 'link'
    ORDER BY mi.menuitem_id
";

$result = $conn->query($query);
$fixes = [];

while ($row = $result->fetch_assoc()) {
    $menuItemID = $row['menuitem_id'];
    $currentTypeID = $row['current_typeid'];
    $correctLinkID = $row['menulink_id'];
    
    if ($correctLinkID && $currentTypeID != $correctLinkID) {
        $fixes[] = [
            'menu_id' => $menuItemID,
            'name' => $row['name'],
            'current_typeid' => $currentTypeID,
            'correct_linkid' => $correctLinkID,
            'link_url' => $row['link']
        ];
    }
}

if (count($fixes) > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-family:monospace; font-size:12px;'>";
    echo "<tr style='background:#ddd;'><th>Menu Item</th><th>Name</th><th>Current TypeID</th><th>Should Be</th><th>Link URL</th><th>Status</th></tr>";
    
    foreach ($fixes as $fix) {
        $updateQuery = "UPDATE {$prefix}menu_item SET itemtype_id = ? WHERE menuitem_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ii", $fix['correct_linkid'], $fix['menu_id']);
        $success = $stmt->execute();
        
        echo "<tr>";
        echo "<td>".$fix['menu_id']."</td>";
        echo "<td>".htmlspecialchars($fix['name'])."</td>";
        echo "<td>".$fix['current_typeid']."</td>";
        echo "<td>".$fix['correct_linkid']."</td>";
        echo "<td>".htmlspecialchars($fix['link_url'])."</td>";
        echo "<td style='color:".($success ? "green" : "red")."'>".($success ? "FIXED" : "FAILED")."</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<p><strong>Fixed ".count($fixes)." menu items!</strong></p>";
} else {
    echo "<p>No fixes needed - all menu items are correct!</p>";
}

// Clean up duplicate link record for Home (LinkID 30)
echo "<h2>Cleaning up duplicate link records</h2>";
$deleteResult = $conn->query("DELETE FROM {$prefix}menuitem_link WHERE menulink_id = 30");
if ($deleteResult) {
    echo "<p style='color:green;'>Deleted duplicate YouTube link (LinkID 30) that was incorrectly assigned to Home</p>";
} else {
    echo "<p style='color:red;'>Failed to delete duplicate link</p>";
}

echo "<h2>After Fix - Menu State</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-family:monospace; font-size:12px;'>";
echo "<tr style='background:#ddd;'><th>ID</th><th>Name</th><th>Type</th><th>TypeID</th><th>Link URL</th></tr>";

$result = $conn->query("
    SELECT mi.menuitem_id, mi.name, mi.itemtype, mi.itemtype_id, ml.link
    FROM {$prefix}menu_item mi
    LEFT JOIN {$prefix}menuitem_link ml ON mi.itemtype_id = ml.menulink_id
    WHERE mi.itemtype = 'link' AND mi.menucategory_id = 3
    ORDER BY mi.sortnum
");

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>".$row['menuitem_id']."</td>";
    echo "<td>".htmlspecialchars($row['name'])."</td>";
    echo "<td>".$row['itemtype']."</td>";
    echo "<td>".$row['itemtype_id']."</td>";
    echo "<td>".htmlspecialchars($row['link'] ?? 'N/A')."</td>";
    echo "</tr>";
}

echo "</table>";

$conn->close();

echo "<p><strong>Done! Refresh your site to see the corrected menu links.</strong></p>";
