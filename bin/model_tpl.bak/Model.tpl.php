require_once MODEL_BASE_PATH . '<?php echo $modelName ?>Base.class.php';

/**
 * @package <?php echo $package ?>
 * @subpackage models
 * @since <?php echo date('Y-m-d h:i:s') ?> 
 */
class <?php echo $modelName ?> extends <?php echo $modelName ?>Base {

	/**
	 * It is not safe for Model concrete implementations to override their
	 * parent's constructor. They can do initialization job in this initialize()
	 * method.
	 */
	protected function initialize() {
		// initialization ...
	}

}