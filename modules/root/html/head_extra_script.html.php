<?php use \UserSession ?>
<script type="text/javascript">
	if (!Oce) Oce = {};
	if (!Oce.Security) Oce.Security = {};
	if (!Oce.Context) Oce.Context = {};
<?php if (UserSession::isIdentified()): ?>
	Oce.Security.initIdentified = true;
	Oce.Security.loginInfos = <?php echo UserSession::getLoginInfos(true) ?>;
<?php else: ?>
	Oce.Security.initIdentified = false;
<?php endif ?>
<?php if (isset($context)): ?>
	Oce.Context = <?php echo $context->raw ?>;
<?php endif ?>
</script>