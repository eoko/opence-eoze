<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 * @license http://www.planysphere.fr/licenses/psopence.txt
 */

require_once LIBS_PATH . 'html2pdf.phar';

class PdfExport {

	private $result;
	private $table;
	private $fields;
	private $title;

	private $html2pdf = null;

	function __construct($result, $fields, ModelTable $table, $title) {
        $this->result = $result;
		$this->table = $table;
		$this->fields = $fields;
		$this->title = $title;
    }

	public function getHtml2Pdf() {
		if (!$this->html2pdf) $this->html2pdf = $this->createObject();
		return $this->html2pdf;
	}

	/**
	 * @return HTML2PDF
	 */
	private function createObject() {
		$html2pdf = new HTML2PDF('L','A4','fr');
		return $html2pdf;
	}

	function output($filename) {
		$html2pdf = $this->getHtml2Pdf();
		$html2pdf->WriteHTML($this->renderHtml());
		$html2pdf->Output($filename);
	}

	function writeFile($filename) {
		$html2pdf = $this->getHtml2Pdf();
		$html2pdf->WriteHTML($this->renderHtml());
		$this->getHtml2Pdf()->Output($filename, 'F');
	}

	private static $var = array('eric' => 'test');
	static function out($name) {
		echo self::$var[$name];
	}

  	/**
	 *
	 * @return string the rendered content string
	 */
	function renderHtml() {

//		global $_SESSION;

		function out($name) {
			PdfExport::out($name);
		}

		function imageUrl($relativePath) {
			echo ROOT . $relativePath;
		}

		function imageTag($relativePath) {
			echo '<img src="' . PHP_PATH . 'php_export' . DS . $relativePath . '" />';
		}

//		self::$var = $_SESSION;
//		$var = self::$var;
//		$body .= Interpretor::renderTable($this->result);

//		echo '<pre>';
//		print_r($this->result);
//		die();

		$tpl = \eoko\template\HtmlTemplate::create()->setFile(PHP_PATH . 'php_export/default.html.php');

		$tpl->user = UserSession::getUser();

		$tpl->date = date("d/m/Y H:i");
		$tpl->title = $this->title;

		if (count($this->result) < 1 || count($this->result[0]) < 1) {
			$tpl->hasData = false;
		} else {
			$tpl->hasData = true;
		}
		$tpl->hasData = true;

		// Header labels
		$tableHeader = array();
		foreach (array_keys($this->result[0]) as $label) {
			$tableHeader[] = $label;
		}

		$tpl->tableHeader = $tableHeader;

		$tpl->tableRows = $this->table->processForPdf($this->fields, $this->result);

//		exit($tpl->renderString());
		return $tpl->render(true);

//		ob_start();
//       	include PHP_PATH . 'php_export/default.html.php';
//		return ob_get_clean();
   	}

}
/*
if (isset($_REQUEST['test'])) test();

function test() {

	global $_SESSION;

	include "param.inc.php";
	include "conn.inc.php";

	// mockup session
    $_SESSION = array(
        'usr.nom' => 'eric',
        'usr.prenom' => 'eric',
        'usr.mail' => 'eric',
    );

    $sort = "nom";
	$dir = "ASC";
	$start = 0;
	$limit = 20;
	$where = "id=id";

	$sql ="SELECT email, username, nom, prenom, actif FROM `users` ";

	$inter = new interpretor;
	$inter->setSQL($sql);
	$inter->setLIMIT("$start,$limit");
	$inter->setORDER("$sort $dir");
	$inter->setWHERE($where);
	$result = $inter->obj();

	for ($i=0; $i<45;$i++) $result[] = $result[$i%2];

//	echo $inter->table();

//	$pdfExport = new PdfExport($result);
//	echo $pdfExport->render();

	$exporter = new Exporter("test_pdf");
	$exporter->exportPDF($result);

}
*/