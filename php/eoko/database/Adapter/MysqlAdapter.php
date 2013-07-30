<?php

namespace eoko\database\Adapter;

use eoko\database\Dumper\MysqlDumper;

use InvalidConfigurationException;
use PDO;

class MysqlAdapter extends AbstractAdapter {

	protected function createConnection() {
		$this->connection = new PDO(
			"mysql:dbname={$this->config->database};host={$this->config->host}",
			$this->config->user,
			$this->config->password
		);

		$this->setNames();

		return $this->connection;
	}

	private function setNames() {
		$cs = $this->config->characterSet;
		$collation = $this->config->collation;
		if ($cs !== null) {
			if ($collation) {
				$this->connection->prepare("SET NAMES '$cs' COLLATE '$collation'")->execute();
			} else {
				throw new \InvalidConfigurationException(
					$this->config,
					"Both collation and characterSet must be set or left blank."
				);
			}
		} else if ($collation !== null) {
			throw new \InvalidConfigurationException(
				$this->config,
				"Both collation and characterSet must be set or left blank."
			);
		}
	}

	public function getDumper() {
		return new MysqlDumper($this->getConfig());
	}

}
