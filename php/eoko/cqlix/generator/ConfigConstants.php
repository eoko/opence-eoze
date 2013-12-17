<?php

namespace eoko\cqlix\generator;

interface ConfigConstants {

	const CFG_COLUMNS = 'columns';

	/**
	 * Relation config, when defined at table level.
	 */
	const CFG_TABLE_RELATIONS = 'relations';

	/**
	 * Relation config, when defined at field level.
	 */
	const CFG_RELATION = 'relation';
	const CFG_ENUM = 'enum';
}
