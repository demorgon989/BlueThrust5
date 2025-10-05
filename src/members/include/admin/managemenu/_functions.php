<?php

if (!defined("MANAGEMENU_FUNCTIONS")) {
	exit();
}

// Validate Functions

function validateMenuItem_Links() {

	if ($_POST['itemtype'] != "link") {
		return false;
	}

	global $linkOptionComponents, $formObj, $cID;

	$linkOptionComponents['linkurl_link']['validate'] = ["NOT_BLANK"];
	$linkOptionComponents['textalign_link']['validate'] = ["RESTRICT_TO_OPTIONS"];
	$linkOptionComponents['targetwindow_link']['validate'] = ["RESTRICT_TO_OPTIONS"];

	$setupFormArgs = [
		"name" => "console-".$cID."-link",
		"components" => $linkOptionComponents
	];

	$localFormObj = new Form($setupFormArgs);

	if (!$localFormObj->validate()) {
		$formObj->errors = array_merge($formObj->errors, $localFormObj->errors);
	}
}

function validateMenuItem_Images() {

	if ($_POST['itemtype'] != "image") {
		return false;
	}

	global $imageOptionComponents, $formObj, $cID;

	$imageOptionComponents['imagefile_image']['validate'] = ["NOT_BLANK"];
	$imageOptionComponents['width_image']['validate'] = ["POSITIVE_NUMBER"];
	$imageOptionComponents['height_image']['validate'] = ["POSITIVE_NUMBER"];
	$imageOptionComponents['textalign_image']['validate'] = ["RESTRICT_TO_OPTIONS"];
	$imageOptionComponents['targetwindow_image']['validate'] = ["RESTRICT_TO_OPTIONS"];

	$setupFormArgs = [
		"name" => "console-".$cID."-image",
		"components" => $imageOptionComponents
	];

	$localFormObj = new Form($setupFormArgs);

	if (!$localFormObj->validate()) {
		$formObj->errors = array_merge($formObj->errors, $localFormObj->errors);
	}
}

function validateMenuItem_CustomPageTypes($pageName, &$formComponents) {

	if ($_POST['itemtype'] != $pageName) {
		return false;
	}

	global $formObj, $cID;

	$textAlign = "textalign_".$pageName;
	$targetWindow = "targetwindow_".$pageName;

	$formComponents[$pageName]['validate'] = ["RESTRICT_TO_OPTIONS"];
	$formComponents[$textAlign]['validate'] = ["RESTRICT_TO_OPTIONS"];
	$formComponents[$targetWindow]['validate'] = ["RESTRICT_TO_OPTIONS"];

	$setupFormArgs = [
		"name" => "console-".$cID."-".$pageName,
		"components" => $formComponents
	];

	$localFormObj = new Form($setupFormArgs);

	if (!$localFormObj->validate()) {
		$formObj->errors = array_merge($formObj->errors, $localFormObj->errors);
	}
}

function validateMenuItem_Poll() {

	if ($_POST['itemtype'] != "poll") {
		return false;
	}

	global $pollOptionComponents, $formObj, $cID;

	$pollOptionComponents['poll']['validate'] = ["RESTRICT_TO_OPTIONS"];

	$setupFormArgs = [
		"name" => "console-".$cID."-poll",
		"components" => $pollOptionComponents
	];

	$localFormObj = new Form($setupFormArgs);

	if (!$localFormObj->validate()) {
		$formObj->errors = array_merge($formObj->errors, $localFormObj->errors);
	}
}


// Save Functions

/*
 * menuComponents - Form Components Array
 * saveObj - Object used to save data to database
 * arrDBNames - DB Names are not set in original component array to avoid being saved with standard menu data
 * 				Set DB table names and values for components with this array
 * ID Column name for saveObj
 *
 *
 */


function saveMenuItem(&$menuComponents, &$saveObj, $arrDBNames, $dbID, $itemType, $saveAdditionalArgs = [], $saveType = "add") {

	if ($_POST['itemtype'] != $itemType) {
		return false;
	}

	global $menuItemObj, $cID, $mysqli;

	// Simple debug to screen
	$GLOBALS['menu_save_debug'][] = "saveMenuItem called for type: ".$itemType;

	foreach ($arrDBNames as $componentName => $dbName) {
		$menuComponents[$componentName]['db_name'] = $dbName;
	}

	// FIX: Query database to find the actual menu item instead of trusting $menuItemObj
	// The $menuItemObj often points to the wrong menu item during afterSave callbacks
	$itemName = $mysqli->real_escape_string($_POST['itemname'] ?? '');
	$menuCategory = (int) ($_POST['menucategory'] ?? 0);

	$query = "SELECT menuitem_id FROM ".$mysqli->get_tablePrefix()."menu_item 
	          WHERE name = ? AND menucategory_id = ? AND itemtype = ? 
	          ORDER BY menuitem_id DESC LIMIT 1";

	$stmt = $mysqli->prepare($query);
	$stmt->bind_param("sis", $itemName, $menuCategory, $itemType);
	$stmt->execute();
	$stmt->bind_result($actualMenuItemID);
	$stmt->fetch();
	$stmt->close();

	$actualMenuItemID = (int) $actualMenuItemID;

	if ($actualMenuItemID === 0) {
		// Fallback to old behavior if query fails
		$actualMenuItemID = $menuItemObj->get_info("menuitem_id");
	}

	$saveAdditional = ["menuitem_id" => $actualMenuItemID];
	$setupFormArgs = [
		"name" => "console-".$cID."-".$itemType,
		"components" => $menuComponents,
		"saveObject" => $saveObj,
		"saveType" => $saveType,
		"saveAdditional" => array_merge($saveAdditional, $saveAdditionalArgs)
	];

	$localFormObj = new Form($setupFormArgs);
	$localFormObj->save();

	// CRITICAL FIX: Use get_keyvalue() which returns the intTableKeyValue set during add()
	$linkID = $saveObj->get_keyvalue();

	// If still 0, determine the table name based on itemType and query for most recent record
	if (empty($linkID) && $actualMenuItemID > 0) {
		// Map item types to their table names
		$tableMap = [
			'link' => 'menuitem_link',
			'image' => 'menuitem_image',
			'custompage' => 'menuitem_custompage',
			'customform' => 'menuitem_custompage',
			'shoutbox' => 'menuitem_shoutbox',
			'customcode' => 'menuitem_customblock',
			'customformat' => 'menuitem_customblock'
		];

		if (isset($tableMap[$itemType])) {
			$tableName = $mysqli->get_tablePrefix().$tableMap[$itemType];
			$queryStr = "SELECT ".$dbID." FROM ".$tableName." 
			             WHERE menuitem_id = '".$actualMenuItemID."' 
			             ORDER BY ".$dbID." DESC LIMIT 1";
			$result = $mysqli->query($queryStr);
			if ($result && $row = $result->fetch_assoc()) {
				$linkID = $row[$dbID];
			}
		}
	}

	// Update the menu item to point to the link/image/etc record
	if ($actualMenuItemID > 0 && !empty($linkID)) {
		$mysqli->query("UPDATE ".$mysqli->get_tablePrefix()."menu_item 
		                SET itemtype_id = '".(int) $linkID."' 
		                WHERE menuitem_id = '".(int) $actualMenuItemID."'");
	}
}


function savePoll() {

	if ($_POST['itemtype'] != "poll") {
		return false;
	}

	global $menuItemObj, $mysqli;

	// FIX: Query database to find the actual menu item
	$itemName = $mysqli->real_escape_string($_POST['itemname'] ?? '');
	$menuCategory = (int) ($_POST['menucategory'] ?? 0);

	$query = "SELECT menuitem_id FROM ".$mysqli->get_tablePrefix()."menu_item 
	          WHERE name = ? AND menucategory_id = ? AND itemtype = 'poll' 
	          ORDER BY menuitem_id DESC LIMIT 1";

	$stmt = $mysqli->prepare($query);
	$stmt->bind_param("si", $itemName, $menuCategory);
	$stmt->execute();
	$stmt->bind_result($actualMenuItemID);
	$stmt->fetch();
	$stmt->close();

	$actualMenuItemID = (int) $actualMenuItemID;

	if ($actualMenuItemID > 0) {
		$mysqli->query("UPDATE ".$mysqli->get_tablePrefix()."menu_item 
		                SET itemtype_id = '".(int) $_POST['poll']."' 
		                WHERE menuitem_id = '".$actualMenuItemID."'");
	} else {
		// Fallback to old behavior
		$menuItemObj->update(["itemtype_id"], [$_POST['poll']]);
	}
}


// Preparation Functions

function prepareItemTypeChangeJS($arr) {

	$innerJS = "";
	foreach ($arr as $ID => $value) {
		$innerJS .= "\$('#".$ID."').hide();\n";
	}

	$innerJS .= "
	switch(\$(this).val()) {
		";

	foreach ($arr as $ID => $value) {
		$innerJS .= "
		case '".$value."':
			\$('#".$ID."').show();
			break;
		";
	}

	$innerJS .= "
	}
	";

	$returnVal = "
	
		$('#itemType').change(function() {
			
		".$innerJS."
		
		});
	
		$('#itemType').change();

	";

	return $returnVal;
}
