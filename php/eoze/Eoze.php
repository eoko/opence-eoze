<?php

namespace eoze;

use eoze\Base\ConfigurableClass;
use eoze\Dependency\Registry;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
class Eoze extends ConfigurableClass {

	private static $eoze = null;

	/**
	 * @var Registry
	 */
	private $registry;

	public function __construct(array $config = null) {
		parent::__construct($config);
		$class = $this->config->get('register().eoze\Dependency\Registry');
		$registers = $this->config->getOr('register()');
		unset($registers['eoze\Dependency\Registry']);
		$this->registry = new $class(array(
			'register()' => $registers,
		));
		$this->registry->register($this->registry);
	}

	/**
	 * @return Eoze
	 */
	public static function getEoze() {
		if (!self::$eoze) {
			self::$eoze = new Eoze;
		}
		return self::$eoze;
	}

	/**
	 * @return Registry
	 */
	public function getRegistry() {
		return self::getEoze()->registry;
	}
}
