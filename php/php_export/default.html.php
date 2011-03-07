<?php use User; ?>
<style>
p.header {
	margin: .8em 0 0 0;
}
p.top-header,
p.footer {
	text-align: center;
	font-size: 2.8mm;
	font-style: italic;
	margin: 0;
}
p.top-header {
	text-align: right;
}
h1#title {
	margin-bottom: 0;
	text-align: center;
	font-size: 6mm;
}
hr, hr.rule {
	border-width: .1mm;
	margin: 0 0 0 0;
}

table.data {
	font-size: 3mm;
	border: .3mm solid #000;
	margin: auto;
}
td {
	padding: .4mm .6mm;
}
td.row1 {
	background-color: #F0F2F2;
}
th {
	font-size: 3.3mm;
	background-color: #f3f3f3;
	padding: 1mm 2mm;
	border-bottom: .3mm solid black;
}
</style>

<page id="page" backtop="4mm" backbottom="7mm" footer="page">
	<page_header>
		<p class="top-header">
			<?php echo $title ?>
			&mdash; <?php _('Édité le %date% par %user%', $date, $user->getDisplayName(User::DNF_FORMAL)) ?>
		</p>
	</page_header>

	<page_footer>
		<hr class="rule"/>
		<p class="footer">
			CIE RHODIA BELLE ETOILE &mdash; SIRET 07307 559 559 40
		</p>
	</page_footer>

<!-- Header -->
	<table width="270mm">
		<tr>
			<td>
				<?php //imageTag('logo.jpg') ?>
				<br/>
				<a href="http://www.opence.com">http://www.opence.com</a>
				<p class="header">
					<?php _('Comité Inter-Entreprise') ?><br/>
					<?php _('Site Belle Étoile') ?>
				</p>
				<p class="header">
					BP 103<br/>
					69192 SAINT FONS CEDEX
				</p>
				<p class="header">
					<?php echo $user->getDisplayName(User::DNF_PRETTY) ?><br/>
					Email: <?php echo $user->getEmail() ?>
				</p>
			</td>
			<td valign="bottom">
				<h1 id="title"><?php echo $title ?></h1>
			</td>
		</tr>
	</table>
<!-- End Header -->

<!--	<h1 id="title">Titre</h1>-->
<!--	<hr class="rule"/>-->

	<?php if (!$hasData): ?>
	<p class="error"><?php _('Aucunes données n\'ont été sélectionnées pour être exportées.') ?></p>
	<?php else: ?>

	<table class="data">
		<?php if (isset($tableHeader)): ?>
		<thead>
			<tr>
				<?php foreach ($tableHeader as $label): ?>
				<th><?php echo $label ?></th>
				<?php endforeach ?>
			</tr>
		</thead>
		<?php endif ?>
		<tbody>
			<?php 
				$iRow = 0;
				foreach ($tableRows as $row):
				$iRow++;
			?>
				<tr>
				<?php foreach ($row as $val): ?>
					<td class="row<?php echo ($iRow % 2 + 1) ?>"><?php echo $val ?></td>
				<?php endforeach ?>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>

	<?php // echo $body ?>

	<?php endif ?>

</page>
