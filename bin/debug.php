<?php

require_once 'init.inc.php';
//Logger::addAppender(new LoggerOutputAppender(false));

Logger::getLogger()->setLevel(Logger::ERROR);
Logger::getLogger('eoko\cache\Cache')->setLevel(Logger::DEBUG);
\eoko\modules\Kepler\CometEvents::disable();

createVersions(MemberTable::getInstance());

function setPrimaryPhoneNumbers() {

	$contactIds = ContactPhoneNumberTable::createQuery()
			->select('contact_id')
			->executeSelectColumn();

	foreach ($contactIds as $cid) {
		$pn = ContactPhoneNumberTable::findFirstWhere("`contact_id` = ?", $cid);
		$pn->setPrimary(true);
		$pn->save();
	}
}

function createVersions(ModelTable $table) {
	
	foreach ($table::findAll() as $record) {
		
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

		$record->setVersion($version);
		$record->save();
		
		echo "Record $record->id done.\n";
	}
}
