<?php
	$paramsPass = explode(', ', $paramsPass);
	array_shift($paramsPass);
	array_unshift($paramsPass, '%%ModelTable%%::getInstance()');
	$paramsPass = implode(', ', $paramsPass);

	$paramsDeclaration = explode(', ', $paramsDeclaration);
	array_shift($paramsDeclaration);
	$paramsDeclaration = implode(', ', $paramsDeclaration);
?>
	<?php echo $doc ?> 
	public static function <?php echo substr($method->getName(), 1) ?>(<?php echo $paramsDeclaration ?>) {
		return Model::<?php echo $method->getName() ?>(<?php echo $paramsPass ?>);
	}

