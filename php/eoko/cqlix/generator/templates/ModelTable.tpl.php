require_once MODEL_BASE_PATH . '<?php echo $className ?>Base.class.php';

/**
 * @package <?php echo $package ?>

 * @subpackage models
 * @since <?php echo date('Y-m-d h:i:s') ?>
 
 */
class <?php echo $className ?> extends <?php echo $className ?>Base {

	/**
	 * It is not safe for ModelTable concrete implementations to override their
	 * parent's constructor. They can do initialization job in this configure()
	 * method.
	 */
	protected function configure() {
		// initialization ...
	}

}
