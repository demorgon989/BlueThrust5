<?php
require_once __DIR__."/_setup.php";
$mysqli->query("SET NAMES 'utf8mb4'");
$result = $mysqli->query("SELECT menuitem_id, name, itemtype, itemtype_id FROM ".$mysqli->get_tablePrefix()."menu_item ORDER BY menuitem_id");
if (!$result) {
    echo "Query failed: ".$mysqli->error.PHP_EOL;
    exit(1);
}
while ($row = $result->fetch_assoc()) {
    echo implode(" | ", [$row['menuitem_id'], $row['name'], $row['itemtype'], $row['itemtype_id']]).PHP_EOL;
}
