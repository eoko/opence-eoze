<?php

namespace eoko\database;

use eoko\config\ConfigManager;
use eoko\util\Arrays;
use eoko\util\collection\ImmutableMap;

class Database {

	/**
	 * @var DatabaseProxy
	 */
	private static $defaultProxy = null;

	/**
	 * @var DatabaseProxy[]
	 */
	private static $namedProxies = array();

	/**
	 * @var DatabaseProxy
	 */
	private $proxy;

	/**
	 * @var Database
	 */
	private static $defaultDatabase;

	public function __construct($proxy) {
		$this->proxy = $proxy;
	}

	/**
	 * @return Database
	 */
	public function getDefault() {
		if (!self::$defaultDatabase) {
			self::$defaultDatabase = new Database(self::getDefaultProxy());
		}
		return self::$defaultDatabase;
	}

	public function getDatabaseName() {
		return self::getProxy($this->proxy)->getAdapter()->getDatabaseName();
	}

	/**
	 * @param $sql
	 * @param bool|callback|\QueryErrorHandler $errorHandler
	 * @return \PDOStatement|bool
	 * @throws \IllegalArgumentException
	 */
	public function query($sql, $errorHandler = null) {

		\Logger::get($this)->debug('Executing raw query: {}', $sql);

		$sth = self::getProxy($this->proxy)->getConnection()->prepare($sql);

		if ($sth->execute()) {
			return $sth;
		} else {
			if ($errorHandler === null) {
				\QueryErrorHandler::getInstance()->process(null, $sth->errorInfo());
			} else {
				if ($errorHandler === false) {
					return false;
				} else if (is_callable($errorHandler)) {
					call_user_func($errorHandler, get_called_class(), $sth->errorInfo());
				} else if ($errorHandler instanceof \QueryErrorHandler) {
					$errorHandler->process(get_called_class(), $sth->errorInfo());
				} else {
					/** @noinspection PhpToStringImplementationInspection */
					throw new \IllegalArgumentException('$errorHandler => ' . $errorHandler);
				}
			}
		}
	}

	/**
	 * @param array|string|DatabaseProxy $proxy
	 * @throws \IllegalArgumentException
	 * @return DatabaseProxy|null
	 */
	public static function setDefaultProxy($proxy = null) {
		$previous = self::$defaultProxy;

		if ($proxy !== null) {
			self::$defaultProxy = self::getProxy($proxy);
		} else {
			self::$defaultProxy = null;
		}

		return $previous;
	}

	/**
	 * @param $name
	 * @param DatabaseProxy|array $proxy
	 */
	public static function registerProxy($name, $proxy) {
		self::$namedProxies[$name] = $proxy;
	}

	/**
	 * @param $proxy
	 * @return DatabaseProxy
	 * @throws \IllegalArgumentException
	 */
	public static function getProxy($proxy) {
		if (is_string($proxy)) {
			return self::getProxyByName($proxy);
		} else if (is_array($proxy)) {
			return new DatabaseProxy($proxy);
		} else if ($proxy instanceof DatabaseProxy) {
			return $proxy;
		} else {
			throw new \IllegalArgumentException(
				'$proxy must be eiter a string, or a DatabaseProxy instance, or an array.'
			);
		}
	}

	/**
	 * @param $name
	 * @return DatabaseProxy
	 * @throws \RuntimeException
	 */
	public static function getProxyByName($name) {
		if (strtolower($name) === 'default') {
			return self::getDefaultProxy();
		}

		if (!isset(self::$namedProxies[$name])) {
			throw new \RuntimeException('No proxy registered with name: ' . $name);
		}

		if (!(self::$namedProxies[$name] instanceof DatabaseProxy)) {
			self::$namedProxies[$name] = new DatabaseProxy(self::$namedProxies[$name]);
		}

		return self::$namedProxies[$name];
	}

	/**
	 * @return DatabaseProxy
	 */
	private static function getDefaultProxy() {
		if (self::$defaultProxy === null) {
			self::$defaultProxy = new DatabaseProxy(ConfigManager::get(__NAMESPACE__));
		}
		return self::$defaultProxy;
	}

	/**
	 * Get config for database from the default node.
	 * @return \eoko\util\collection\Map
	 */
	public static function getDefaultConfig() {
		return self::getDefaultProxy()->getConfig();
	}

	/**
	 * @return Adapter
	 */
	public static function getDefaultAdapter() {
		return self::getDefaultProxy()->getAdapter();
	}

	/**
	 * @return \PDO
	 */
	public static function getDefaultConnection() {
		return self::getDefaultProxy()->getConnection();
	}
}
