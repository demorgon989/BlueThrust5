<?php
require '_config.php';

$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname, 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$prefix = $dbprefix;

echo "<h2>Menu Link Records - Including Prefix Field</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-family:monospace; font-size:11px;'>";
echo "<tr style='background:#ddd;'><th>LinkID</th><th>MenuItemID</th><th>Link URL</th><th>Prefix</th><th>Text Align</th><th>Target</th></tr>";

$result = $conn->query("SELECT * FROM {$prefix}menuitem_link ORDER BY menulink_id");
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>".$row['menulink_id']."</td>";
    echo "<td>".$row['menuitem_id']."</td>";
    echo "<td>".htmlspecialchars($row['link'])."</td>";
    echo "<td>".htmlspecialchars($row['prefix'] ?? 'NULL')."</td>";
    echo "<td>".$row['textalign']."</td>";
    echo "<td>".$row['linktarget']."</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Menu Items (Link Type Only)</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-family:monospace; font-size:11px;'>";
echo "<tr style='background:#ddd;'><th>MenuItemID</th><th>Name</th><th>Type</th><th>TypeID (LinkID)</th><th>Should Link To</th></tr>";

$result = $conn->query("SELECT mi.*, ml.menulink_id 
                        FROM {$prefix}menu_item mi
                        LEFT JOIN {$prefix}menuitem_link ml ON ml.menuitem_id = mi.menuitem_id
                        WHERE mi.itemtype = 'link'
                        ORDER BY mi.menucategory_id, mi.sortnum");
while ($row = $result->fetch_assoc()) {
    $match = ($row['itemtype_id'] == $row['menulink_id']) ? "✅" : "❌ MISMATCH";
    echo "<tr>";
    echo "<td>".$row['menuitem_id']."</td>";
    echo "<td>".htmlspecialchars($row['name'])."</td>";
    echo "<td>".$row['itemtype']."</td>";
    echo "<td>".$row['itemtype_id']."</td>";
    echo "<td>".$row['menulink_id']." ".$match."</td>";
    echo "</tr>";
}

echo "</table>";

$conn->close();
