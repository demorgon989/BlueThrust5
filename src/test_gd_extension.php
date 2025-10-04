<?php
// Test GD Extension
echo "<h2>GD Extension Test</h2>";

if (extension_loaded('gd')) {
    echo "✅ GD Extension is LOADED<br>";
    $gd_info = gd_info();
    foreach ($gd_info as $key => $value) {
        echo "$key: " . ($value ? 'Yes' : 'No') . "<br>";
    }
} else {
    echo "❌ GD Extension is NOT LOADED<br>";
}

echo "<br><h3>Testing CAPTCHA Functions:</h3>";

// Test critical functions
$functions = ['imagecreatetruecolor', 'imagettftext', 'imagepng', 'imagecolorallocate'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "✅ $func() exists<br>";
    } else {
        echo "❌ $func() missing<br>";
    }
}

echo "<br><h3>Font Directory Check:</h3>";
$font_dir = __DIR__ . '/images/captcha-fonts/';
if (is_dir($font_dir)) {
    echo "✅ Font directory exists: $font_dir<br>";
    $fonts = glob($font_dir . '*.ttf');
    echo "Fonts found: " . count($fonts) . "<br>";
    foreach ($fonts as $font) {
        echo "- " . basename($font) . "<br>";
    }
} else {
    echo "❌ Font directory missing: $font_dir<br>";
}
?>