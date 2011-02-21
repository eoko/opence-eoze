<?php

namespace eoko\database;

use eoko\util\collection\Map;

interface Adapter {
	
	function __construct(Map $config);

	/** @return PDO */
	function getConnection();

	/**
	 * Tests whether the given (sql) table matches the configured table prefix.
	 * @param string $sqlTableName
	 * @return boolean TRUE if the given $name is prefixed with the configured
	 * table prefix or if no table prefix is configured, else FALSE.
	 */
	function testTablePrefix($dbTableName);

	function internalTableNameToDatabase($internalTableName);

	/**
	 * Converts the name of a table as it appears in the datastore to the name
	 * used internally by Cqlix (that is, the name minused from its prefix, if
	 * one is configured).
	 * @return string
	 * @throws \InvalidArgumentException if the given $dbTableName is not a
	 * valid name because its prefix doesn't match the configured one.
	 */
	function databaseTableNameToInternal($dbTableName);

	/**
	 * @return string
	 */
	function getDatabaseName();

	/**
	 * @return Map
	 */
	function getConfig();
}
