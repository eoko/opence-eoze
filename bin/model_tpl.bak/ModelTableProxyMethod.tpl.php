	<?php echo $doc ?> 
	public static function <?php echo substr($method->getName(), 1) ?>(<?php echo $paramsDeclaration ?>) {
		return self::getInstance()-><?php echo $method->getName() ?>(<?php echo $paramsPass ?>);
	}

