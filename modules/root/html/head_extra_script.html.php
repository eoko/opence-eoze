<script type="text/javascript">
	if (!Oce) Oce = {};
	if (!Oce.Security) Oce.Security = {};
	if (!Oce.Context) Oce.Context = {};
<?php if (isset($loginInfos)): ?>
	Oce.Security.initIdentified = true;
	Oce.Security.loginInfos = <?php echo $loginInfos->raw ?>;
<?php else: ?>
	Oce.Security.initIdentified = false;
<?php endif ?>
<?php if (isset($context)): ?>
	Oce.Context = <?php echo $context->raw ?>;
<?php endif ?>
</script>
