<?php
require '_config.php';
require 'classes/btmysql.php';

$db = new btmysql($dbhost, $dbuser, $dbpass, $dbname, 3306);

$result = $db->query('SELECT menuitem_id, name, itemtype, itemtype_id, menucategory_id, sortnum FROM '.$db->get_tablePrefix().'menu_item ORDER BY menucategory_id, sortnum');

echo "Menu Items in Database:\n";
echo str_repeat("=", 80)."\n";
printf("%-5s | %-20s | %-15s | %-12s | %-8s | %-8s\n", "ID", "Name", "Type", "TypeID", "Category", "SortNum");
echo str_repeat("-", 80)."\n";

while($row = $result->fetch_assoc()) {
    printf("%-5s | %-20s | %-15s | %-12s | %-8s | %-8s\n", 
        $row['menuitem_id'], 
        substr($row['name'], 0, 20), 
        $row['itemtype'], 
        $row['itemtype_id'], 
        $row['menucategory_id'], 
        $row['sortnum']
    );
}
