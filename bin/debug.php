<?php

require_once 'init.inc.php';
Logger::addAppender(new LoggerOutputAppender(false));

use eoko\util\YmlReader;

//$m = SmModule::load(38);
//
//dump($m->smModuleSaisons->toArray());


$id = 12799; // non membre
$id = 2; // non membre
$id = 1; // Abel Anthony
$id = 2148; // Abdrone Françoise
$id = 1061; // Abdelouhab

$context = array(
	'year' => 2010
);
$c = Contact::load($id, $context);
$c2 = Contact::load(1, $context);

dump(array(
	$c,
	$c2,
	$c2->isChildOf($c),
));

//dump($c->getRelation('Enfant')->get());
//dump('' . $c->getEnfant()->getParent());

$types = $c->getTypes();

foreach ($types as &$type) {
	$type = $type->name();
}

dump(array(
	$types,
	$c->getConjoint()
));

//dump("$c");

$m = $c->membre;

dump($m);
