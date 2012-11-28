<?php

class MailHelper {

	const CCI = 'cci';
	const TO  = 'to';
	const CC  = 'cc';

	public static function massMail(array $emails, $title, $body, $html = true) {
		$html = $html ? 'true' : 'false';

		// Debug implode emails
		if (!ArrayHelper::isAssoc($emails)) {
			$emails = array('to' => $emails);
		} else {
			$emails = array(
				'to' => isset($emails[self::TO]) ? $emails[self::TO] : array(),
				'cc' => isset($emails[self::CC]) ? $emails[self::CC] : array(),
				'cci' => isset($emails[self::CCI]) ? $emails[self::CCI] : array(),
			);
		}
		foreach ($emails as &$v) $v = implode(', ', $v);

		Logger::dbg(
			<<<MAIL_TEXT
Simulate mass mailing:
Title:	$title
Body:	$body
html:	$html
mode:	$mode
to:		$emails[to]
cc:		$emails[cc]
cci:		$emails[cci]
MAIL_TEXT
		);
	}

	/**
	 *
	 * @param type $to
	 * 
	 * @param string $from The expedient name and address. Must be either the raw
	 * email address (e.g. eric@example.com) or the name and email in the form
	 * 'Éric O. <eric@example.com>'. If the string doesn't contains angle brackets
	 * (<>) they will be added around the whole string, considered in this case 
	 * as a raw email.
	 * 
	 * @param type $subject
	 * @param type $message
	 * @param type $bcc
	 * @param type $cc
	 * @return type 
	 */
	public static function sendHTML($to, $from, $subject, $message, $bcc = null, $cc = null) {

		if (is_array($to)) {
			$to = implode(', ', $to);
		}

		$headersExtra = '';
		if ($cc !== null) {
			if (is_array($cc)) $cc = implode(', ', $cc);
			$headersExtra .= "Cc: $cc\r\n";
		}
		if ($bcc !== null) {
			if (is_array($bcc)) $bcc = implode(', ', $bcc);
			$headersExtra .= "Bcc: $bcc\r\n";
		}

		// Each line should be separated with a LF (\n). Lines should not be larger 
		// than 70 characters.
		// http://fr.php.net/manual/en/function.mail.php
		$message = wordwrap($message, 70, "\n", true);

		$message = <<<MAIL
<html>
<head>
<title>$subject</title>
</head>
<body>
$message
</body>
</html>
MAIL;

		// From
		if (!strstr($from, '<')) {
			$from = "<$from>";
		}

		// Always set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\r\n";
//		$headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
		$headers .= "Content-type:text/html;charset=utf-8" . "\r\n";

		// More headers
		$headers .= "From: $from\r\n";
		$headers .= $headersExtra;

//		Logger::dbg(
//			'Sending mail: [subject: {} ; message: {} ; to: {} ; bcc: {}]',
//			$subject, $message, $to, $bcc
//		);

		return mail($to,$subject,$message,$headers);
	}

	public static function isValid($mail) {

		if ($mail === null) return false;

		return 1 === preg_match(
			'/^([\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+\.)*[\w\!\#$\%\&\'\*\+\-'
			.'\/\=\?\^\`{\|\}\~]+@((((([a-z0-9]{1}[a-z0-9\-]{0,62}[a-z0-9]{1})|'
			.'[a-z])\.)+[a-z]{2,6})|(\d{1,3}\.){3}\d{1,3}(\:\d{1,5})?)$/i'
			, $mail
		);
	}
}
