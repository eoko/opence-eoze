<?php
/**
 * Logiciel : exemple d'utilisation de HTML2PDF
 * 
 * Convertisseur HTML => PDF
 * Distribu� sous la licence LGPL. 
 *
 * @author		Laurent MINGUET <webmaster@html2pdf.fr>
 * 
 * isset($_GET['vuehtml']) n'est pas obligatoire
 * il permet juste d'afficher le r�sultat au format HTML
 * si le param�tre 'vuehtml' est pass� en param�tre _GET
 */
 	// r�cup�ration du contenu HTML
 	ob_start();
 	$num = 'CMD01-'.date('ymd');
 	$nom = 'DUPONT Alphonse';
 	$date = '01/01/2010';
?>
<style type="text/css">
<!--
	div.zone { border: none; border-radius: 6mm; background: #FFFFFF; border-collapse: collapse; padding:3mm; font-size: 2.7mm;}
	h1 { padding: 0; margin: 0; color: #DD0000; font-size: 7mm; }
	h2 { padding: 0; margin: 0; color: #222222; font-size: 5mm; position: relative; }
-->
</style>
<page format="100x200" orientation="L" backcolor="#AAAACC" style="font: arial;">
	<div style="rotate: 90; position: absolute; width: 100mm; height: 4mm; left: 195mm; top: 0; font-style: italic; font-weight: normal; text-align: center; font-size: 2.5mm;">
		Ceci est votre e-ticket � pr�senter au contr�le d'acc�s -
		billet g�n�r� par <a href="http://html2pdf.fr/" style="color: #222222; text-decoration: none;">html2pdf</a>
	</div>
	<table style="width: 99%;border: none;" cellspacing="4mm" cellpadding="0">
		<tr>
			<td colspan="2" style="width: 100%">
				<div class="zone" style="height: 34mm;position: relative;font-size: 5mm;">
					<div style="position: absolute; right: 3mm; top: 3mm; text-align: right; font-size: 4mm; ">
						<b><?php echo $nom; ?></b><br>
					</div>
					<div style="position: absolute; right: 3mm; bottom: 3mm; text-align: right; font-size: 4mm; ">
						<b>1</b> place <b>plein tarif</b><br>
						Prix unitaire TTC : <b>45,00&euro;</b><br>
						N� commande : <b><?php echo $num; ?></b><br>
						Date d'achat : <b><?php echo date('d/m/Y � H:i:s'); ?></b><br> 
					</div>
					<h1>Billet soir�e sp�cial HTML2PDF</h1>
					&nbsp;&nbsp;&nbsp;&nbsp;<b>Valable le <?php echo $date; ?> � 20h30</b><br>
					<img src="./res/logo.gif" alt="logo" style="margin-top: 3mm; margin-left: 20mm">
				</div>
			</td>
		</tr>
		<tr>
			<td style="width: 25%;">
				<div class="zone" style="height: 40mm;vertical-align: middle;text-align: center;">
					<qrcode value="<?php echo $num."\n".$nom."\n".$date; ?>" ec="Q" style="width: 37mm; border: none;" ></qrcode>
				</div>
			</td>
			<td style="width: 75%">
				<div class="zone" style="height: 40mm;vertical-align: middle;">
					<b>Conditions d'utilisation du billet</b><br>
					Le billet est soumis aux conditions g�n�rales de vente que vous avez
					accept�es avant l'achat du billet. Le billet d'entr�e est uniquement
					valable s'il est imprim� sur du papier A4 blanc, vierge recto et verso.
					L�entr�e est soumise au contr�le de la validit� de votre billet. Une bonne
					qualit� d�impression est n�cessaire. Les billets partiellement imprim�s,
					souill�s, endommag�s ou illisibles ne seront pas accept�s et seront
					consid�r�s comme non valables. En cas d'incident ou de mauvaise qualit�
					d'impression, vous devez imprimer � nouveau votre fichier. Pour v�rifier
					la bonne qualit� de l'impression, assurez-vous que les informations �crites
					sur le billet, ainsi que les pictogrammes (code � barres 2D) sont bien
					lisibles. Ce titre doit �tre conserv� jusqu'� la fin de la manifestation.
					Une pi�ce d'identit� pourra �tre demand�e conjointement � ce billet. En
					cas de non respect de l'ensemble des r�gles pr�cis�es ci-dessus, ce billet
					sera consid�r� comme non valable.<br>
					<br>
					<i>Ce billet est reconnu �lectroniquement lors de votre
					arriv�e sur site. A ce titre, il ne doit �tre ni dupliqu�, ni photocopi�.
					Toute reproduction est frauduleuse et inutile.</i>
				</div>
			</td>
		</tr>
	</table>
</page>
<?php
 	$content = ob_get_clean();
	
	// conversion HTML => PDF
	require_once(dirname(__FILE__).'/../html2pdf.class.php');
	try
	{
		$html2pdf = new HTML2PDF('P','A4','fr', false, 'ISO-8859-15', 0);
		$html2pdf->pdf->SetDisplayMode('fullpage');
		$html2pdf->writeHTML($content, isset($_GET['vuehtml']));
		$html2pdf->Output('billet.pdf');
	}
	catch(HTML2PDF_exception $e) { echo $e; }
	