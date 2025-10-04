<?php
echo "Simple PHP Test<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Files in directory:<br>";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "- $file<br>";
    }
}
?>