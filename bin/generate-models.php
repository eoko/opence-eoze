#!usr/bin/php
<?php

$class = 'eoko\\cqlix\\generator\\LegacyGenerator';
require __DIR__ . '/doRun.php';

$syncDbAction = 'dump';
require __DIR__ . '/db-sync.php';

require __DIR__ . '/test-database-sync.php';
