<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 */

if (isset($_GET['olog'])) {
	Logger::addAppender(new LoggerOutputAppender());
}

Logger::addAppender(new LoggerFirePHPAppender());
