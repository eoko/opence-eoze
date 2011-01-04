<head>
	<title><?php echo $title->raw ?></title>

<?php if (isset($meta)) foreach ($meta as $equiv => $content): ?>
<?php if (is_array($content)) foreach ($content as $content): ?>
	<meta http-equiv="<?php echo $equiv ?>" content="<?php echo $content ?>" />
<?php endforeach; else ?>
	<meta http-equiv="<?php echo $equiv ?>" content="<?php echo $content ?>" />
<?php endforeach ?>
	
<?php if (isset($css)) foreach ($css as $url): ?>
	<link rel="stylesheet" type="text/css" href="<?php echo $url ?>" />
<?php endforeach ?>
	
<?php if (isset($js)) foreach ($js as $url): ?>
	<script type="text/javascript" src="<?php echo $url ?>"></script>
<?php endforeach ?>
	
<?php if (isset($extra)) tabEcho(1, $extra) ?>
</head>