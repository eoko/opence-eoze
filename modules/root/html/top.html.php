<div class="box-right">
<?php if ($user): ?>
    <h1><?php //_('Bonjour %firstname% %lastname%', $user->getPrenom(), $user->getNom()) ?></h1>
    <p><?php //_('Bienvenue sur openCE') ?></p>
    <p><?php //_('Votre compte est valide jusqu\'au %date%', $user->getEndUse(\DateHelper::DATE_LOCALE)) ?></p>
	<p>
		<a href="#" onclick="Oce.mx.Security.logout()"><?php _('Déconnection') ?></a> -
		<a href="#"><?php _('Voir mes paramètres') ?></a>
	</p>
<?php else: ?>
	<h1><?php _("Problème d'identification") ?></h1>
<?php endif ?>
</div>