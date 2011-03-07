namespace <?php echo $namespace ?>;

use eoko\module\ModulesLocation;

class <?php echo $class ?> extends \<?php echo $superClass ?> {

	public function __construct($name, $basePath, $url, $upperPathsUrl, ModulesLocation $baseLocation) {
		
		self::addPathsUrl($upperPathsUrl, array(
			"<?php echo $gridModulePath ?>" => "<?php echo $gridModuleUrl ?>"
		));
	
		parent::__construct($name, $basePath, $url, $upperPathsUrl, $baseLocation);
	}
}