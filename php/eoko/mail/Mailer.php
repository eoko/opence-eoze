<?php
/**
 * Copyright (C) 2012 Eoko
 *
 * This file is part of Opence.
 *
 * Opence is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Opence is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Opence. If not, see <http://www.gnu.org/licenses/gpl.txt>.
 *
 * @copyright Copyright (C) 2012 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

namespace eoko\mail;

use RuntimeException;

/**
 *
 * @category Eoze
 * @package mail
 * @since 2012-11-29 11:21
 */
class Mailer {

	const CCI = 'cci';
	const TO  = 'to';
	const CC  = 'cc';

	/**
	 * @var string|null
	 */
	private $robotEmail = null;

	private $charset = 'utf-8';

	public static function create() {
		return new static;
	}

	/**
	 * If set, this email will be substituted to the `from` field. This is useful to prevent
	 * being flagged as fishing if the system is not allowed to send emails from the true
	 * sender domain.
	 *
	 * The following variables can be used and will be replaced with values extracted from
	 * the given `from` field or from the server info:
	 *
	 * - `%sender.name%`
	 * - `%sender.email%`
	 * - `%server.domain%`
	 *
	 * @param string $email
	 * @return Mailer
	 */
	public function setRobotEmail($email) {
		$this->robotEmail = $email;
		return $this;
	}

	/**
	 * Sets the character set of the message.
	 *
	 * @param string $charset
	 * @return Mailer
	 */
	public function setCharset($charset) {
		$this->charset = $charset;
		return $this;
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
	public function sendHTML($to, $from, $subject, $message, $bcc = null, $cc = null) {

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

		// Extract sender infos
		$sender = (object) array(
			'email' => null,
			'name' => null,
		);
		if (preg_match('/(?<name>^[^<]*)?<(?<email>[^>]*)>$/', $from, $matches)) {
			foreach ($sender as $k => $v) {
				if (isset($matches[$k]) && $matches[$k]) {
					$sender->$k = trim($matches[$k]);
				}
			}
		} else {
			$sender->email = $from;
			$from = "<$from>";
		}

		// Always set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= 'Content-type:text/html;charset=' . $this->charset . "\r\n";

		// From
		if ($this->robotEmail) {
			$headers .= "Reply-To: $from\r\n";
			$from = $this->buildRobotEmail($sender);
		}
		$headers .= "From: $from\r\n";

		// More headers
		$headers .= $headersExtra;

//		Logger::dbg(
//			'Sending mail: [subject: {} ; message: {} ; to: {} ; bcc: {}]',
//			$subject, $message, $to, $bcc
//		);

		return mail($to,$subject,$message,$headers);
	}

	/**
	 * @param object $sender
	 * @return string
	 * @throws \RuntimeException
	 */
	private function buildRobotEmail($sender) {
		$from = $this->robotEmail;

		if (!$from) {
			return $from;
		}

		if (strstr($from, '%server.domain%', $from)) {
			if (!isset($_SERVER['SERVER_NAME'])) {
				$host = $_SERVER['SERVER_NAME'];
			} else if (isset($_SERVER['HTTP_HOST'])) {
				$host = $_SERVER['HTTP_HOST'];
			} else {
				throw new RuntimeException('Cannot read server hostname');
			}
			$from = str_replace("%server.domain%", $host, $from);
		}

		foreach ($sender as $k => $v) {
			$from = str_replace("%sender.$k%", $v, $from);
		}

		return $from;
	}

	/**
	 * Tests an email validity.
	 *
	 * @param $mail
	 * @return bool
	 */
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
