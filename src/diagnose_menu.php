<?php
// Quick diagnostic to see menu items and their actual links
require '_config.php';

$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname, 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Menu Items with Links</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-family:monospace; font-size:12px;'>";
echo "<tr style='background:#ddd;'><th>ID</th><th>Name</th><th>Type</th><th>TypeID</th><th>Category</th><th>Sort</th><th>Link Details</th></tr>";

$prefix = $dbprefix;

// Get all menu items
$result = $conn->query("SELECT * FROM {$prefix}menu_item ORDER BY menucategory_id, sortnum");
while ($row = $result->fetch_assoc()) {
    $linkDetails = "-";
    
    // If it's a link type, get the actual link
    if ($row['itemtype'] == 'link' && $row['itemtype_id'] > 0) {
        $linkResult = $conn->query("SELECT link FROM {$prefix}menuitem_link WHERE menulink_id = ".$row['itemtype_id']);
        if ($linkResult && $linkRow = $linkResult->fetch_assoc()) {
            $linkDetails = htmlspecialchars($linkRow['link']);
        }
    } elseif ($row['itemtype'] == 'donation') {
        $linkDetails = "Campaign ID: ".$row['itemtype_id'];
    }
    
    echo "<tr>";
    echo "<td>".$row['menuitem_id']."</td>";
    echo "<td>".htmlspecialchars($row['name'])."</td>";
    echo "<td>".$row['itemtype']."</td>";
    echo "<td>".$row['itemtype_id']."</td>";
    echo "<td>".$row['menucategory_id']."</td>";
    echo "<td>".$row['sortnum']."</td>";
    echo "<td>".$linkDetails."</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Menu Links Table</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-family:monospace; font-size:12px;'>";
echo "<tr style='background:#ddd;'><th>LinkID</th><th>MenuItemID</th><th>Link URL</th><th>Target</th></tr>";

$result = $conn->query("SELECT * FROM {$prefix}menuitem_link ORDER BY menulink_id");
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>".$row['menulink_id']."</td>";
    echo "<td>".$row['menuitem_id']."</td>";
    echo "<td>".htmlspecialchars($row['link'])."</td>";
    echo "<td>".$row['linktarget']."</td>";
    echo "</tr>";
}

echo "</table>";

$conn->close();
