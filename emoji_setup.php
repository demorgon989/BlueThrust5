<?php
/*
 * BlueThrust v5.8 Emoji Support Setup
 * Enables emoji support for existing installations
 *
 * REQUIREMENTS:
 * 1. Update your BlueThrust installation to the latest version first
 * 2. This tool only migrates the database - code must be updated separately
 *
 * This file should be deleted after successful setup
 */

// Include BlueThrust setup files to define MAIN_ROOT and other constants
require_once('_setup.php');

// Prevent direct access without proper setup
if (!defined('MAIN_ROOT')) {
	$current_dir = getcwd();
	$missing_files = [];

	// Check for essential BlueThrust files
	$required_files = ['_setup.php', '_config.php', 'classes/btmysql.php'];
	$required_dirs = ['classes', 'js', 'src'];

	foreach ($required_files as $file) {
		if (!file_exists($file)) {
			$missing_files[] = $file;
		}
	}

	foreach ($required_dirs as $dir) {
		if (!is_dir($dir)) {
			$missing_files[] = $dir . '/ (directory)';
		}
	}

	if (!empty($missing_files)) {
		echo '<h2>BlueThrust Root Directory Not Detected</h2>';
		echo '<p>This tool must be placed in your BlueThrust root directory.</p>';
		echo '<p><strong>Current directory:</strong> ' . $current_dir . '</p>';
		echo '<p><strong>Missing files/directories:</strong></p>';
		echo '<ul>';
		foreach ($missing_files as $missing) {
			echo '<li>' . htmlspecialchars($missing) . '</li>';
		}
		echo '</ul>';
		echo '<p>Please ensure this file is in the same directory as your main BlueThrust files (index.php, _setup.php, etc.).</p>';
		exit;
	} else {
		echo '<h2>Setup Error</h2>';
		echo '<p>BlueThrust setup files found but MAIN_ROOT constant not defined. This may indicate a configuration issue.</p>';
		echo '<p>Please check your _setup.php and _config.php files.</p>';
		exit;
	}
}

$pageTitle = 'BlueThrust v5.8 Emoji Support Setup';
$message = '';
$success = false;
$step = isset($_GET['step']) ? $_GET['step'] : 'check';

try {
	// Database connection is already established by _setup.php
	require_once('classes/btmysql.php');

	$mysqli = new btmysql($dbhost, $dbuser, $dbpass, $dbname);
	$mysqli->set_tablePrefix($dbprefix);

	switch ($step) {
		case 'check':
			// Check if this is the updated version
			if (!file_exists('js/tiny_mce/tinymce.min.js')) {
				$message = '<div class="error">❌ TinyMCE files not found. Please update your BlueThrust installation first using the installer.</div>
                           <p>This emoji setup tool requires the latest BlueThrust code with TinyMCE emoji support.</p>';
				break;
			}

			// Check current database charset
			$result = $mysqli->query("SHOW CREATE TABLE `".$dbprefix."forum_post`");
			if ($result && $row = $result->fetch_assoc()) {
				if (strpos($row['Create Table'], 'utf8mb4') !== false) {
					$message = '<div class="success">✅ Emoji support is already enabled! Your database is using utf8mb4 charset.</div>';
					$success = true;
				} else {
					$message = '<div class="info">ℹ️ Emoji support needs to be enabled. Your database is using utf8 charset.</div>
                               <p><a href="?step=migrate" class="button">Enable Emoji Support</a></p>';
				}
			} else {
				$message = '<div class="error">❌ Could not check database charset. Please check your database configuration.</div>';
			}
			break;

		case 'migrate':
			// Run the migration
			$sql = "ALTER TABLE `".$dbprefix."forum_post` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
			if ($mysqli->query($sql)) {
				$message = '<div class="success">✅ Database migration completed successfully!</div>
                           <p><a href="?step=test" class="button">Test Emoji Functionality</a></p>';
			} else {
				$message = '<div class="error">❌ Database migration failed: ' . $mysqli->error . '</div>';
			}
			break;

		case 'test':
			// Test emoji functionality
			$testMessage = 'Emoji Test: 😀 🎉 👍 🚀 ❤️ (Test post - will be deleted)';
			$testSubject = 'Emoji Support Test';

			// Insert test post
			$insertSQL = "INSERT INTO `".$dbprefix."forum_post` (title, message, member_id, dateposted, forumtopic_id) VALUES (?, ?, 1, ?, 1)";
			$stmt = $mysqli->prepare($insertSQL);
			$currentTime = time();
			$stmt->bind_param('ssi', $testSubject, $testMessage, $currentTime);

			if ($stmt->execute()) {
				$testPostId = $stmt->insert_id;

				// Retrieve and verify
				$selectSQL = "SELECT message FROM `".$dbprefix."forum_post` WHERE forumpost_id = ?";
				$stmt2 = $mysqli->prepare($selectSQL);
				$stmt2->bind_param('i', $testPostId);
				$stmt2->execute();
				$result = $stmt2->get_result();

				if ($result && $row = $result->fetch_assoc()) {
					if ($row['message'] === $testMessage) {
						$message = '<div class="success">✅ Emoji functionality test passed! Emojis are working correctly.</div>';
						$success = true;

						// Clean up test post
						$mysqli->query("DELETE FROM `".$dbprefix."forum_post` WHERE forumpost_id = $testPostId");

						$message .= '<div class="warning">🗑️ Test data cleaned up. Setup complete!</div>
                                    <div class="delete-notice">
                                        <h3>🎉 Setup Complete!</h3>
                                        <p>Emoji support is now fully enabled on your BlueThrust installation.</p>
                                        <p><strong>Important:</strong> Delete this file (<code>emoji_setup.php</code>) from your server for security.</p>
                                    </div>';
					} else {
						$message = '<div class="error">❌ Emoji test failed - data corruption detected. Migration may not have worked properly.</div>';
					}
				} else {
					$message = '<div class="error">❌ Could not retrieve test data. Please check your database.</div>';
				}

				$stmt2->close();
			} else {
				$message = '<div class="error">❌ Could not insert test data: ' . $stmt->error . '</div>';
			}

			$stmt->close();
			break;
	}
} catch (Exception $e) {
	$message = '<div class="error">❌ Setup error: ' . $e->getMessage() . '</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $pageTitle; ?></title>
	<style>
		body {
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			margin: 0;
			padding: 0;
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.container {
			background: white;
			border-radius: 12px;
			box-shadow: 0 20px 40px rgba(0,0,0,0.1);
			padding: 40px;
			max-width: 600px;
			width: 90%;
			text-align: center;
		}

		h1 {
			color: #333;
			margin-bottom: 30px;
			font-size: 2.5em;
		}

		.logo {
			font-size: 3em;
			margin-bottom: 20px;
		}

		.message {
			margin: 20px 0;
			padding: 20px;
			border-radius: 8px;
			font-size: 1.1em;
		}

		.success {
			background: #d4edda;
			color: #155724;
			border: 1px solid #c3e6cb;
		}

		.error {
			background: #f8d7da;
			color: #721c24;
			border: 1px solid #f5c6cb;
		}

		.info {
			background: #d1ecf1;
			color: #0c5460;
			border: 1px solid #bee5eb;
		}

		.warning {
			background: #fff3cd;
			color: #856404;
			border: 1px solid #ffeaa7;
		}

		.delete-notice {
			background: #e8f5e8;
			border: 2px solid #4caf50;
			border-radius: 8px;
			padding: 20px;
			margin-top: 20px;
		}

		.button {
			display: inline-block;
			background: #007bff;
			color: white;
			padding: 12px 30px;
			text-decoration: none;
			border-radius: 6px;
			font-weight: bold;
			transition: background 0.3s;
			border: none;
			cursor: pointer;
			font-size: 1.1em;
		}

		.button:hover {
			background: #0056b3;
			text-decoration: none;
			color: white;
		}

		.step-indicator {
			display: flex;
			justify-content: center;
			margin-bottom: 30px;
		}

		.step {
			width: 30px;
			height: 30px;
			border-radius: 50%;
			background: #ddd;
			color: #666;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: bold;
			margin: 0 10px;
		}

		.step.active {
			background: #007bff;
			color: white;
		}

		.step.completed {
			background: #28a745;
			color: white;
		}

		.emoji {
			font-size: 1.5em;
		}
	</style>
</head>
<body>
	<div class="container">
		<div class="logo">🎉</div>
		<h1><?php echo $pageTitle; ?></h1>

		<div class="step-indicator">
			<div class="step <?php echo ($step == 'check' || $step == 'migrate' || $step == 'test') ? 'active' : ''; ?>">1</div>
			<div class="step <?php echo ($step == 'migrate' || $step == 'test') ? 'active' : ''; ?>">2</div>
			<div class="step <?php echo $step == 'test' ? 'active' : ''; ?>">3</div>
		</div>

		<div class="message">
			<?php echo $message; ?>
		</div>

		<?php if ($success): ?>
		<div style="margin-top: 30px;">
			<p style="font-size: 1.2em; color: #28a745; font-weight: bold;">
				🎊 Your BlueThrust installation now supports emojis! 🎊
			</p>
		</div>
		<?php endif; ?>

		<div style="margin-top: 40px; font-size: 0.9em; color: #666;">
			<p>BlueThrust v5.8 Emoji Support Setup</p>
		</div>
	</div>
</body>
</html>
