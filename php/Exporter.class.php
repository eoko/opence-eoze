<?php

class Exporter {

	private $name = 'export';
	private $saltFileNameEnd = true;
	/** @var Controller */
	private $controller = null;
	private static $exportsAbsolutePath = EXPORTS_PATH;

	public function __construct($basename = 'Export', Controller $moduleController = null) {
		$this->controller = $moduleController;
		$this->setNAME($basename, false);
   	}

	public function setNAME($name, $salfFileNameEnd = true){
		$this->name = $name;
		if ($this->controller !== null) $this->name .= '_' . $this->controller->getModule();
		$this->saltFileNameEnd = $salfFileNameEnd;
	}

	public function getFileName($extension) {

		$salt = !$this->saltFileNameEnd ? '' : '_' . $this->generateRandomEndSalt(5);
		$timestamp = '_' . date("YmdHis");

      	$filename = Inflector::capitalizeWords($this->name . $salt . $timestamp);

		if (substr($extension, -1, 1) != '.') $extension = '.' . $extension;

		if (!(substr($filename, -strlen($extension) ) === $extension)) {
			$filename .= $extension;
		}

		return $filename;
	}

    public function exportCSV($result) {

		$filename = $this->getFileName('csv');
		$file = fopen(self::getAbsolutePath($filename), 'w');

		if ($file === false) {
			throw new SystemException('Cannot write to file: "{}"', $filename);
		}

		$sep = "\t";

        $isFirstLine = true;
        foreach ($result as $array) {

			if ($isFirstLine) {
				$line = chr(255).chr(254);
                foreach (array_keys($array) as $row) {
                    $line .= $row . $sep;
                }
                $isFirstLine = false;
				$line .= "\n";
			} else {
				$line = "\n";
			}


            foreach ($array as $row) {
                $line .= $row . $sep;
            }

			$line = mb_convert_encoding($line, 'UTF-16LE', 'UTF-8');
			fwrite($file, $line, strlen($line));
        }

		fclose($file);

		return self::getUrl($filename);
//
//        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
//        header("Content-Length: " . strlen($html));
//        header("Content-type: text/x-csv");
//        header("Content-Disposition: attachment; filename=" . $this->getFileName('csv'));
//
//        return $html;
    }

	private static function getUrl($filename) {
		return EXPORTS_BASE_URL . $filename;
	}

	private static function getAbsolutePath($filename) {
		return self::$exportsAbsolutePath . $filename;
	}

	private function generateRandomEndSalt($nLetters) {
        $r = '';
        for ($i = 0; $i < $nLetters; $i++) {
            $r .= chr(rand(0, 25) + ord('A'));
        }
		return $r;
	}

	public function exportPDF($result, $fields, ModelTable $table, $title) {
		$pdfExport = new PdfExport($result, $fields, $table, $title);
		$filename = $this->getFileName('pdf');
		$pdfExport->writeFile(self::getAbsolutePath($filename));
		return self::getUrl($filename);
    }

}

