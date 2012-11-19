<?php
//require_once('../../php/Logger.php');



class Spriter {


    private $files;
    private $dest;
    private $imgwidth;
    private $imgheight;
    private $spacing;
    private $addgreyscale;
    private $imgtype;
    private $height;
    private $width;
    private $counter;
    private $img;

    public function getCounter() {
        return $this->counter;
    }

    public function __construct() {
        $this->counter = 0;
        $this->files = array();
        $this->dest;
        $this->imgheight = 32;
        $this->imgwidth = 32;
        $this->imgtype = 'png';
        $this->spacing = 0;
        $this->addgreyscale = false;
        $this->height = $this->imgheight;
        $this->width = 0;
        $this->height = 0;

    }

    public function getFiles() {
        return $this->files;
    }

    public function setFiles($files) {
        $this->files = $files;
    }

    public function getDest() {
        return $this->dest;
    }

    public function setDest($dest) {
        $this->dest = $dest;
    }

    public function getImgwidth() {
        return $this->imgwidth;
    }

    public function setImgwidth($imgwidth) {
        $this->imgwidth = $imgwidth;
    }

    public function getImgheight() {
        return $this->imgheight;
    }

    public function setImgheight($imgheight) {
        $this->imgheight = $imgheight;
    }

    public function getSpacing() {
        return $this->spacing;
    }

    public function setSpacing($spacing) {
        $this->spacing = $spacing;
    }

    public function getAddgreyscale() {
        return $this->addgreyscale;
    }

    public function setAddgreyscale($addgreyscale) {
        $this->addgreyscale = $addgreyscale;
    }

    public function getImgtype() {
        return $this->imgtype;
    }

    public function setImgtype($imgtype) {
        $this->imgtype = $imgtype;
    }

    public function getHeight() {
        return $this->height;
    }

    public function getWidth() {
        return $this->width;
    }

    public function setWidth($width) {
        $this->width = $width;
    }

    public function setHeight($height) {
        $this->height = $height;
    }

    /**
     * Permit to set param in one shot
     *
     * @param array $files
     * @param string $dest
     * @param integer $imgwidth
     * @param integer $imgheight
     * @param integer $spacing
     * @param boolean $addgreyscale
     * @param integer $imgtype
     */
    public function setFastParam($files, $dest, $imgwidth, $imgheight, $spacing, $addgreyscale, $imgtype) {
        $this->files = $files;
        $this->dest = $dest;
        $this->imgwidth = $imgwidth;
        $this->imgheight = $imgheight;
        $this->spacing = $spacing;
        $this->addgreyscale = $addgreyscale;
        $this->imgtype = $imgtype;
        echo "<pre>";
        print_r($this);
        echo "</pre>";
    }

    /**
     *
     * Create the sprite support
     * @return $img
     */
    private function constructImage() {
        $img = imagecreatetruecolor($this->width, $this->height);
        $background = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $background);
        imagealphablending($img, false);
        imagesavealpha($img, true);

        return $this->img = $img;
    }

    /**
     * Create the sprite
     */
    public function sprite() {

        if ($this->addgreyscale) {
            $this->height = $this->imgheight + $this->spacing + $this->imgheight;
        } else {
            $this->height = $this->imgheight;
        }

        $files_tmp = array();

        foreach ($this->files as $file) {
            list($w, $h, $t) = getimagesize($file);

            if (($w == $this->imgwidth) && ($h == $this->imgheight)) {
                $this->width = ( $this->spacing + $this->imgwidth);
                $files_tmp[] = array('file' => $file, 'type' => $t);
            }
        }

        $this->constructImage();
        $pos = 0;
        $this->counter = 0;


        foreach ($files_tmp as $file) {
            $this->counter += 1;

            if ($file['type'] == IMAGETYPE_GIF) {
                $tmp = imagecreatefromgif($file['file']);
            } elseif ($file['type'] == IMAGETYPE_JPEG) {
                $tmp = imagecreatefromjpeg($file['file']);
            } elseif ($file['type'] == IMAGETYPE_PNG) {
                $tmp = imagecreatefrompng($file['file']);
            } else {
                echo('Error : Wrong filetypes' . '<br/>Filetype found :' . $file['type']);
            }

            imagecopy($this->img, $tmp, $pos, 0, 0, 0, $this->imgwidth, $this->imgheight);
            if ($this->addgreyscale) {
                imagefilter($tmp, IMG_FILTER_GRAYSCALE);
                imagecopy($this->img, $tmp, $pos, $this->imgheight + $this->spacing, 0, 0, $this->imgwidth,
                        $this->imgheight);
            }
            $pos += ( $this->spacing + $this->imgwidth);
            imagedestroy($tmp);
        }

        
    }

    public function printFile() {
        if ($this->counter==1) {
            if (empty($this->dest)) {
                if ($this->imgtype == "png") {
                    header('Content-Type: image/png');
                    imagepng($this->img);
                } elseif ($this->imgtype == "gif") {
                    header('Content-Type: image/gif');
                    imagegif($this->img);
                } elseif ($this->imgtype == "jpeg") {
                    header('Content-Type: image/jpeg');
                    imagejpeg($this->img);
                }
            }
        } else {
            echo "<b>Vous ne pouvez afficher qu'une image</b><br/>";
        }
    }

    public function saveFile() {
        if ($this->counter > 0) {
            if ($this->imgtype == "png") {
                if (imagepng($this->img, $this->dest)) {
                    echo "<a href='$this->dest'>$this->dest</a><br/>";
                }
            } elseif ($this->imgtype == "gif") {
                if (imagegif($this->img, $this->dest)) {
                    echo "<a href='$this->dest'>$this->dest</a><br/>";
                    ;
                }
            } elseif ($this->imgtype == "jpeg") {
                if (imagejpeg($this->img, $this->dest)) {
                    echo "<a href='$this->dest'>$this->dest</a><br/>";
                    ;
                }
            }
        } else {
            echo "<b>There are no image in $this->dest </b><br/>";
        }
    }

}

