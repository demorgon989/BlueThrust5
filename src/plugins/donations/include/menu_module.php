<?php

if (!function_exists('donation_debug_output')) {
	function donation_debug_output($message) {
	// Debug output disabled for production
		return;
	}
}

if (!function_exists('donation_admin_log')) {
	function donation_admin_log($message) {
		$logFile = BASE_DIRECTORY."plugins/donations/debug.log";
		$timestamp = date("Y-m-d H:i:s");
		@file_put_contents($logFile, "[ADMIN][".$timestamp."] ".trim($message)."\n", FILE_APPEND);
	}
}

function donationManageMenuItem() {
	// Disabled—edit_item.php doesn't use this hook
}

function donationAddMenuItem() {
	global $mysqli, $formObj, $arrAfterJS, $arrItemTypeChangesJS;

	$arrComponents = $formObj->components;
	$menuItemObj = $formObj->objSave;

	// Get Sort Order
	$sortOrder = $arrComponents['fakeSubmit']['sortorder'];

	$arrComponents['fakeSubmit']['sortorder'] = $sortOrder+1;

	// Add donation campaign to list of item types
	$arrComponents['itemtype']['options']['donation'] = "Donation Campaign";

	// Donation Section Options
	$donationOptions = [];
	$donationOptions[''] = "Pick Campaign";
	$result = $mysqli->query("SELECT * FROM ".$mysqli->get_tablePrefix()."donations_campaign ORDER BY title");
	while ($row = $result->fetch_assoc()) {
		$donationOptions[$row['donationcampaign_id']] = filterText($row['title']);
	}

	if (count($donationOptions) == 1) {
		$donationOptions['none'] = "No Campaigns Running";
	}

	$donationSectionOptions = [
		"donation_campaign" => [
			"type" => "select",
			"display_name" => "Select Campaign",
			"attributes" => ["class" => "formInput textBox"],
			"options" => $donationOptions
		]
	];

	// Add new section for donations
	$arrComponents['donationoptions'] = [
		"type" => "section",
		"options" => ["section_title" => "Donation Campaign Options:"],
		"sortorder" => $sortOrder,
		"attributes" => ["id" => "donationCampaign", "style" => "display: none"],
		"components" => $donationSectionOptions
	];

	// Modify JS for new donation section
	$arrItemTypeChangesJS['donationCampaign'] = "donation";
	$arrAfterJS['itemType'] = prepareItemTypeChangeJS($arrItemTypeChangesJS);

	$afterJS = "

			$(document).ready(function() {
			";

	foreach ($arrAfterJS as $value) {
		$afterJS .= $value."\n";
	}

	$afterJS .= "		
			});
			
		";

	// Apply new components to form
	$formObj->components = $arrComponents;
	$formObj->embedJS = $afterJS;
	$formObj->afterSave[] = "saveDonationMenuItem";
}


function saveDonationMenuItem() {
	global $menuItemObj, $mysqli;

	donation_admin_log("saveDonationMenuItem called with itemtype: ".($_POST['itemtype'] ?? 'NONE'));
	donation_admin_log("saveDonationMenuItem POST: ".json_encode($_POST));

	if ($_POST['itemtype'] != "donation") {
		donation_admin_log("Itemtype is not 'donation', exiting save handler");
		return false;
	}

	$rawCampaignValue = $_POST['donation_campaign'] ?? "";
	$selectedCampaign = (is_numeric($rawCampaignValue) && $rawCampaignValue !== '') ? (int) $rawCampaignValue : 0;

	// The $menuItemObj is pointing to the WRONG menu item!
	// We need to query for the menu item that was just created/updated
	// by looking for the itemname and itemtype from POST

	$itemName = $mysqli->real_escape_string($_POST['itemname'] ?? '');
	$menuCategory = (int) ($_POST['menucategory'] ?? 0);

	// Find the menu item by name, category, and itemtype
	$query = "SELECT menuitem_id FROM ".$mysqli->get_tablePrefix()."menu_item 
	          WHERE name = ? AND menucategory_id = ? AND itemtype = 'donation' 
	          ORDER BY menuitem_id DESC LIMIT 1";

	$stmt = $mysqli->prepare($query);
	$stmt->bind_param("si", $itemName, $menuCategory);
	$stmt->execute();
	$stmt->bind_result($menuItemID);
	$stmt->fetch();
	$stmt->close();

	$menuItemID = (int) $menuItemID;

	if ($menuItemID === 0) {
		donation_admin_log("ERROR: Cannot find menu item with name '$itemName' in category $menuCategory; aborting.");
		return false;
	}

	donation_admin_log("Campaign selected: ".$selectedCampaign);
	donation_admin_log("Found actual menu item ID: ".$menuItemID);

	// Direct database update - bypass caching entirely
	$stmt = $mysqli->prepare("UPDATE ".$mysqli->get_tablePrefix()."menu_item SET itemtype_id = ? WHERE menuitem_id = ?");
	if ($stmt) {
		$stmt->bind_param("ii", $selectedCampaign, $menuItemID);
		$success = $stmt->execute();
		$stmt->close();
		donation_admin_log("Direct UPDATE result: ".($success ? "true" : "false"));

		// Verify what got stored
		$verify = $mysqli->query("SELECT itemtype_id, itemtype FROM ".$mysqli->get_tablePrefix()."menu_item WHERE menuitem_id = ".$menuItemID);
		if ($verify && $row = $verify->fetch_assoc()) {
			donation_admin_log("Verified stored itemtype_id: ".$row['itemtype_id'].", itemtype: ".$row['itemtype']);
		}
	} else {
		donation_admin_log("Failed to prepare UPDATE statement");
	}
}

function displayDonationMenuModule() {
	try {
		$menuItemInfo = $GLOBALS['menu_item_info'];
		if ($menuItemInfo['itemtype'] != "donation") {
			return false;
		}

		global $mysqli;
		if (!class_exists("DonationCampaign", false)) {
			require_once(BASE_DIRECTORY."plugins/donations/classes/campaign.php");
		}

		$campaignID = (int) $menuItemInfo['itemtype_id'];
		if ($campaignID === 0) {
			return false;
		}

		$campaignObj = new DonationCampaign($mysqli);
		if (!$campaignObj->select($campaignID)) {
			return false;
		}

		$linkText = filterText($menuItemInfo['name']);
		$linkURL = $campaignObj->getLink();

		// Add prefix (bullet point) to match other menu items
		$prefix = "<b>&middot;</b> ";

		echo "<div class='menuLinks'>".$prefix."<a href='".$linkURL."'>".$linkText."</a></div>";
	} catch (Throwable $e) {
		// Silently fail - don't show debug in production
		return false;
	}
}


function initDonationMenuMod() {
	global $hooksObj, $mysqli, $btThemeObj;

	$modsConsoleObj = new ConsoleOption($mysqli);
	$modsManageMenusCID = $modsConsoleObj->findConsoleIDByName("Manage Menu Items");

	$modsAddMenusCID = $modsConsoleObj->findConsoleIDByName("Add Menu Item");

	$hooksObj->addHook("menu_item", "displayDonationMenuModule");
	$hooksObj->addHook("console-".$modsAddMenusCID, "donationAddMenuItem");
	$hooksObj->addHook("console-".$modsManageMenusCID, "donationManageMenuItem");

	$btThemeObj->addHeadItem("donation-css", "<link rel='stylesheet' type='text/css' href='".MAIN_ROOT."plugins/donations/donations.css'>");
}



initDonationMenuMod();
