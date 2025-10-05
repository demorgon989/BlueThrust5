<?php

require_once("basic.php");

class WebsiteInfo extends Basic {

	protected $arrKeys;
	protected $blnRefreshInfo;
	protected $strPagePath;
	public $objBTMail;

	public function __construct($sqlConnection) {

		$this->MySQL = $sqlConnection;
		$this->strTableName = $this->MySQL->get_tablePrefix()."websiteinfo";
		$this->strTableKey = "websiteinfo_id";

		$this->arrKeys = [];
		$this->blnRefreshInfo = true;
		$this->objBTMail = new btMail();
	}


	public function select($intIDNum, $numericIDOnly = true) {
		$temp = $this->arrObjInfo;
		$returnVal = parent::select($intIDNum, $numericIDOnly);

		if ($this->blnRefreshInfo) {
			$this->arrObjInfo = [];
			$result = $this->MySQL->query("SELECT * FROM ".$this->strTableName);
			while ($row = $result->fetch_assoc()) {
				$this->arrObjInfo[$row['name']] = $row['value'];
				$this->arrKeys[$row['name']] = $row['websiteinfo_id'];
			}
			$this->blnRefreshInfo = false;

			// Update btMail with SMTP settings
			$this->updateBTMailSettings();
		} else {
			$this->arrObjInfo = $temp;
		}

		return $returnVal;
	}


	/** Update btMail object with current SMTP settings */
	private function updateBTMailSettings() {
		$smtpSettings = [
			'smtp_mode' => $this->arrObjInfo['smtp_mode'] ?? 'auto',
			'smtp_host' => $this->arrObjInfo['smtp_host'] ?? '',
			'smtp_port' => $this->arrObjInfo['smtp_port'] ?? '',
			'smtp_username' => $this->arrObjInfo['smtp_username'] ?? '',
			'smtp_password' => $this->arrObjInfo['smtp_password'] ?? '',
			'smtp_encryption' => $this->arrObjInfo['smtp_encryption'] ?? ''
		];

		// Create new btMail instance with updated settings
		$this->objBTMail = new btMail($smtpSettings);
	}

	public function multiUpdate($arrSettings, $arrValues) {

		$countErrors = 0;
		foreach ($arrSettings as $key => $settingName) {
			if ($this->select($this->arrKeys[$settingName])) {
				if (!$this->update(["value"], [$arrValues[$key]])) {
					$countErrors++;
				}
			} else {
				if (!$this->addNew(["name", "value"], [$settingName, $arrValues[$key]])) {
					$countErrors++;
				}
			}
		}

		// Refresh settings and update btMail after changes
		if ($countErrors == 0) {
			$this->blnRefreshInfo = true;
			$this->select(1);
		}

		return ($countErrors == 0);
	}

	public function update($arrColumns, $arrValues) {

		$this->blnRefreshInfo = true;
		return parent::update($arrColumns, $arrValues);
	}


	public function get_key($settingName) {
		return $this->arrKeys[$settingName];
	}


	public function setPage($pagePath) {
		$this->strPagePath = $pagePath;
	}

	public function displayPage() {
		global $mysqli, $dbprefix, $hooksObj;
		require_once(BASE_DIRECTORY.$this->strPagePath);
	}


}
