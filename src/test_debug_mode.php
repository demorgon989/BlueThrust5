<?php
// Test Debug Mode Settings
echo "<h2>Debug Mode Test</h2>";

try {
    require_once('_setup.php');
    echo "✅ Setup file loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Error loading setup: " . $e->getMessage() . "<br>";
    exit();
}

// Check if debug mode is enabled
if (isset($websiteInfo['debugmode'])) {
    echo "Debug Mode Setting: " . ($websiteInfo['debugmode'] == 1 ? '✅ ON' : '❌ OFF') . "<br>";
} else {
    echo "❌ Debug mode setting not found in websiteInfo<br>";
}

// Check current error reporting level
$error_level = error_reporting();
echo "Current Error Reporting Level: $error_level<br>";

if ($error_level == E_ALL) {
    echo "✅ Error reporting is set to E_ALL (maximum)<br>";
} else {
    echo "⚠️ Error reporting is not at maximum level<br>";
}

// Check if we can access the database
if (isset($mysqli)) {
    echo "✅ Database connection available<br>";
    
    // Check websiteinfo table
    $result = $mysqli->query("SELECT debugmode FROM {$dbprefix}websiteinfo LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        echo "Database Debug Mode: " . ($row['debugmode'] == 1 ? '✅ ON' : '❌ OFF') . "<br>";
    } else {
        echo "❌ Could not read debug mode from database<br>";
    }
} else {
    echo "❌ No database connection<br>";
}

// Test path to admin settings
echo "<br><h3>Admin Settings Path:</h3>";
$admin_settings_path = __DIR__ . '/members/include/admin/sitesettings.php';
if (file_exists($admin_settings_path)) {
    echo "✅ Admin settings file exists<br>";
    echo "Path: $admin_settings_path<br>";
} else {
    echo "❌ Admin settings file not found<br>";
}

// Trigger a test warning if debug mode is on
if ($websiteInfo['debugmode'] == 1) {
    echo "<br><h3>Test Warning (should show if debug mode works):</h3>";
    $undefined_variable_test = $this_variable_does_not_exist;
    echo "If you see a warning above, debug mode is working!<br>";
}
?>