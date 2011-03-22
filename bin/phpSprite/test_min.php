<?php

require_once('../CSSTidy/class.csstidy.php');



$css = new csstidy();
$dir = "../../css/";
$extension = "*.css";

$array = array();
$array_size = array();


foreach (glob($dir . $extension) as $filename) {
    $css->parse_from_url($filename);
    if (!empty($css->css)) {
        foreach ($css->css['41'] as $row) {
            if (isset($row["background-image"])) {
                $str = $row["background-image"];
                $str = preg_replace('/^url\(([\'"]?)([^\'")]+)\1\).*$/', '$2', $str);
                if ($str != "none") {
                    if (file_exists($dir . $str)) {
                        array_push($array, $dir . $str);

                        $size = getimagesize($dir . $str);

                        if (!in_array($size[0], $array_size)) {

                            array_push($array_size, $size[0]);
                        }
                    }
                }
            }
        }
    }
}

//print_r($css->css);
//echo $css->print->formatted();
//
require_once('spriter.class.php');
//foreach ($array as $row) {
//    echo $row . "<br/>";
//}

$spriter = new Spriter();

foreach ($array_size as $s) {
    echo $s;
    $spriter->setFastParam($array, 'test_sprite/sprite' . $s . '.png', $s, $s, 0, true, 'png');
    $spriter->sprite();
    $spriter->saveFile();
}
?>
