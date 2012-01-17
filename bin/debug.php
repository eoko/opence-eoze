<?php

require_once 'init.inc.php';
//Logger::addAppender(new LoggerOutputAppender(false));

Logger::getLogger()->setLevel(Logger::ERROR);
Logger::getLogger('eoko\cache\Cache')->setLevel(Logger::DEBUG);

foreach (ContactTable::findAll() as $contact) {
	$contact instanceof Contact;
	
	$version = DataVersion::create();
	
	$rev = DataVersionRevision::create(array(
		'version_id' => $version->getId(),
		'datetime'   => date('Y-m-d H:i:s'), // now
		'user_id'    => 97,
		'action'     => 'CREATE',
	));
	
	$version->setLastRevisionNumber(1);
	$version->setRevisions(array($rev));
//	$version->addRevision('CREATE');
	
	$contact->setVersion($version);
	$contact->save();
}