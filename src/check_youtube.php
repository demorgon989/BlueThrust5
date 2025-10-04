<?php
require '_config.php';

$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname, 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$prefix = $dbprefix;

echo "<h2>YouTube Menu Item Status</h2>";

// Find YouTube menu item
$result = $conn->query("SELECT * FROM {$prefix}menu_item WHERE name LIKE '%YouTube%' OR name LIKE '%youtube%'");

if ($result && $result->num_rows > 0) {
    $youtube = $result->fetch_assoc();
    echo "<h3>Menu Item:</h3>";
    echo "<pre>";
    print_r($youtube);
    echo "</pre>";
    
    // Check what link it points to
    if ($youtube['itemtype'] == 'link' && $youtube['itemtype_id'] > 0) {
        $linkResult = $conn->query("SELECT * FROM {$prefix}menuitem_link WHERE menulink_id = ".$youtube['itemtype_id']);
        if ($linkResult && $linkResult->num_rows > 0) {
            echo "<h3>Link Record (ID ".$youtube['itemtype_id']."):</h3>";
            echo "<pre>";
            print_r($linkResult->fetch_assoc());
            echo "</pre>";
        } else {
            echo "<p style='color:red;'>No link record found for itemtype_id ".$youtube['itemtype_id']."</p>";
        }
    } else {
        echo "<p style='color:red;'>itemtype_id is 0 or not a link type</p>";
    }
    
    // Check ALL link records for this menu item
    $allLinks = $conn->query("SELECT * FROM {$prefix}menuitem_link WHERE menuitem_id = ".$youtube['menuitem_id']);
    echo "<h3>All Link Records for Menu Item ".$youtube['menuitem_id'].":</h3>";
    if ($allLinks && $allLinks->num_rows > 0) {
        while ($link = $allLinks->fetch_assoc()) {
            echo "<pre>";
            print_r($link);
            echo "</pre>";
        }
    } else {
        echo "<p>No link records found</p>";
    }
    
} else {
    echo "<p>YouTube menu item not found</p>";
}

// Show menu item above YouTube
echo "<h2>Menu Item Above YouTube</h2>";
$result = $conn->query("SELECT mi.*, ml.link, ml.prefix 
                        FROM {$prefix}menu_item mi
                        LEFT JOIN {$prefix}menuitem_link ml ON mi.itemtype_id = ml.menulink_id
                        WHERE mi.menucategory_id = 3
                        ORDER BY mi.sortnum");

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>Sort</th><th>Type</th><th>TypeID</th><th>Link URL</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>".$row['menuitem_id']."</td>";
    echo "<td>".htmlspecialchars($row['name'])."</td>";
    echo "<td>".$row['sortnum']."</td>";
    echo "<td>".$row['itemtype']."</td>";
    echo "<td>".$row['itemtype_id']."</td>";
    echo "<td>".htmlspecialchars($row['link'] ?? 'N/A')."</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();

echo "<h2>Debug Log (if available)</h2>";
if (file_exists('/var/www/html/menu_save_debug.txt')) {
    echo "<pre>";
    echo htmlspecialchars(file_get_contents('/var/www/html/menu_save_debug.txt'));
    echo "</pre>";
} else {
    echo "<p>No debug log found</p>";
}
