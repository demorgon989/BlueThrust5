<?php
require '_config.php';

$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname, 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$prefix = $dbprefix;

echo "<h2>Fixing YouTube Menu Item</h2>";

// YouTube menu item 79 should point to link 31
$result = $conn->query("UPDATE {$prefix}menu_item SET itemtype_id = 31 WHERE menuitem_id = 79");

if ($result) {
    echo "<p style='color:green;'>✅ Fixed YouTube - now points to link record 31</p>";
} else {
    echo "<p style='color:red;'>❌ Failed: ".$conn->error."</p>";
}

// Verify
$result = $conn->query("SELECT mi.menuitem_id, mi.name, mi.itemtype_id, ml.link 
                        FROM {$prefix}menu_item mi
                        LEFT JOIN {$prefix}menuitem_link ml ON mi.itemtype_id = ml.menulink_id
                        WHERE mi.menuitem_id = 79");
                        
if ($row = $result->fetch_assoc()) {
    echo "<h3>Verification:</h3>";
    echo "<p>Menu Item ID: ".$row['menuitem_id']."</p>";
    echo "<p>Name: ".$row['name']."</p>";
    echo "<p>Points to Link ID: ".$row['itemtype_id']."</p>";
    echo "<p>Link URL: ".htmlspecialchars($row['link'] ?? 'NULL')."</p>";
    
    if ($row['itemtype_id'] == 31 && $row['link'] == 'youtube.com') {
        echo "<p style='color:green; font-weight:bold;'>✅ SUCCESS! YouTube is correctly configured!</p>";
    }
}

$conn->close();

echo "<p><strong>Refresh your site - YouTube link should now work!</strong></p>";
