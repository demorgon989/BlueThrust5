<?php

/** Class to make some things simpler with PHPMailer */
class btMail {

	private $objPHPMailer;
	private $smtpSettings = [];

	/** Constructor - optionally accepts SMTP settings */
	public function __construct($smtpSettings = []) {
		$this->smtpSettings = $smtpSettings;
	}

	/** Configure SMTP settings for PHPMailer */
	private function configureSMTP($mail) {
		$mode = $this->smtpSettings['smtp_mode'] ?? 'auto';
		$host = $this->smtpSettings['smtp_host'] ?? '';
		$port = $this->smtpSettings['smtp_port'] ?? '';
		$username = $this->smtpSettings['smtp_username'] ?? '';
		$password = $this->smtpSettings['smtp_password'] ?? '';
		$encryption = $this->smtpSettings['smtp_encryption'] ?? '';

		// Check if we should use SMTP
		$useSMTP = false;
		if ($mode === 'smtp') {
			$useSMTP = true; // Force SMTP
		} elseif ($mode === 'auto' && !empty($host) && !empty($username) && !empty($password)) {
			$useSMTP = true; // Auto mode with valid settings
		} elseif ($mode === 'mail') {
			$useSMTP = false; // Force mail()
		}

		if ($useSMTP && !empty($host)) {
			$mail->isSMTP();
			$mail->Host = $host;
			$mail->SMTPAuth = true;
			$mail->Username = $username;
			$mail->Password = $password;

			if (!empty($port)) {
				$mail->Port = (int) $port;
			}

			if (!empty($encryption)) {
				$mail->SMTPSecure = $encryption;
			}

			// Allow self-signed certificates (common with self-hosted mail servers)
			$mail->SMTPOptions = array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				)
			);

			// Enable debugging
			$mail->SMTPDebug = 0; // Disable verbose debug output for production
			$mail->Debugoutput = function($str, $level) {
				$debugMsg = "SMTP Debug [{$level}]: {$str}";
				error_log($debugMsg);
				// Only show debug on screen if debug mode is enabled
				global $websiteInfo;
				if (isset($websiteInfo['debugmode']) && $websiteInfo['debugmode'] == 1) {
					echo $debugMsg . "<br>";
				}
			};
		}
	}

	/** General e-mail function using PHPMailer */
	public function sendMail($to, $subject, $message, $additional = []) {

		$mail = new PHPMailer();
		$this->objPHPMailer = $mail;

		// Configure SMTP if settings are available
		$this->configureSMTP($mail);

		$from = $this->getFrom($additional);

		// Check if the from has both email and name
		if (is_array($from)) {
			$mail->setFrom($from['email'], $from['name']);
		} else {
			$mail->setFrom($from);
		}

		$this->addEmail(["to" => $to]);

		$mail->Subject = $subject;

		$mail->msgHTML($message);

		$this->addEmail($additional, "bcc");
		$this->addEmail($additional, "cc");

		$result = $mail->send();

		// Log the result for debugging
		if ($result) {
			error_log("Email sent successfully to: $to");
		} else {
			error_log("Email failed to: $to - Error: " . $mail->ErrorInfo);
		}

		return $result;
	}

	private function getFrom($args) {

		if (!isset($args['from'])) {
			// If SMTP is configured with a username, use it as the from address
			if (!empty($this->smtpSettings['smtp_username'])) {
				global $CLAN_NAME;
				$from = ["email" => $this->smtpSettings['smtp_username'], "name" => $CLAN_NAME ?? "Admin"];
			} else {
				$siteDomain = $_SERVER['SERVER_NAME'];
				if (substr($siteDomain, 0, strlen("www.")) == "www.") {
					$siteDomain = substr($siteDomain, strlen("www."));
				}
				$from = "admin@".$siteDomain;
			}
		} elseif (is_array($args['from'])) {
			// From is already an array with email/name
			$from = $args['from'];
		} elseif (trim($args['from']) == "") {
			// If SMTP is configured with a username, use it as the from address
			if (!empty($this->smtpSettings['smtp_username'])) {
				global $CLAN_NAME;
				$from = ["email" => $this->smtpSettings['smtp_username'], "name" => $CLAN_NAME ?? "Admin"];
			} else {
				$siteDomain = $_SERVER['SERVER_NAME'];
				if (substr($siteDomain, 0, strlen("www.")) == "www.") {
					$siteDomain = substr($siteDomain, strlen("www."));
				}
				$from = "admin@".$siteDomain;
			}
		} else {
			$from = $args['from'];
		}

		return $from;
	}


	private function addEmail($args, $type = "to") {

		$mail = $this->objPHPMailer;

		switch ($type) {
			case "bcc":
				$func = "addBCC";
				break;
			case "cc":
				$func = "addCC";
				break;
			default:
				$func = "addAddress";
		}

		if (isset($args[$type]) && is_array($args[$type])) {
			foreach ($args[$type] as $info) {
				if (is_array($info)) {
					call_user_func_array([$mail, $func], [$info['email'], $info['name']]);
				} else {
					call_user_func_array([$mail, $func], [$info]);
				}
			}
		} elseif (isset($args[$type])) {
			call_user_func_array([$mail, $func], [$args[$type]]);
		}
	}

}
