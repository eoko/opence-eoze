<?php use \UserSession ?>
<script type="text/javascript">
	if (!Oce) {
		Oce = {};
		if (!Oce.Security) Oce.Security = {};
	}
<?php if (UserSession::isIdentified()): ?>
	Oce.Security.initIdentified = true;
	Oce.Security.loginInfos = <?php echo UserSession::getLoginInfos(true) ?>;
<?php else: ?>
	Oce.Security.initIdentified = false;
<?php endif ?>
</script>