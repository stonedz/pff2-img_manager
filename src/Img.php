<?php

namespace pff\modules;

use pff\Abs\AModule;
use pff\Iface\IConfigurableModule;

/**
 * Manages images
 */
class Img extends AModule implements IConfigurableModule
{
    private $_resize;
    private $_width;
    private $_height;
    private $_thumb_width;
    private $_thumb_height;
    private $dest;
    private $validMimeTypes;
    private $maxFileSize;
    private $_dpi;

    public function __construct($confFile = 'pff2-img_manager/module.conf.local.yaml')
    {
        $this->loadConfig($confFile);
        $this->dest = ROOT.DS.'app'.DS.'public'.DS.'files';
    }

    public function loadConfig($confFile)
    {
        $conf                 = $this->readConfig($confFile);
        $this->_resize        = $conf['moduleConf']['resize'];
        $this->_width         = $conf['moduleConf']['width'];
        $this->_height        = $conf['moduleConf']['height'];
        $this->_thumb_width   = $conf['moduleConf']['thumb_width'];
        $this->_thumb_height  = $conf['moduleConf']['thumb_height'];
        $this->_dpi           = $conf['moduleConf']['dpi'];
        $this->_compression   = $conf['moduleConf']['compression_quality'];
        $this->validMimeTypes = $conf['moduleConf']['validMimeTypes'];
        $this->maxFileSize    = $conf['moduleConf']['maxFileSize'];
    }

    /**
     * @param $fileArray
     * @param bool $create_thumb
     * @param $destination // relative to app/public/files directory, no trailing slash
     *
     * @return bool|string
     */
    public function saveImage($fileArray, $create_thumb = false, $destination = false, $multi=false)
    {
        $tmp_file = $fileArray['tmp_name'];
        $name     = $fileArray['name'];

        if (!$destination) {
            $dest = $this->dest;
        } else {
            $dest = $this->dest . DS . $destination;
        }

        $img = new \Imagick($tmp_file);
        $img_width = $img->getimagewidth();
        $img_height = $img->getimageheight();

        if(function_exists('exif_read_data')){
            $exif = exif_read_data($tmp_file);
            if (isset($exif['Orientation'])) {
                $orientation = $exif['Orientation'];
                switch ($orientation) {

                case 6: // rotate 90 degrees CW
                    $img->rotateimage("#FFF", 90);
                    break;

                case 8: // rotate 90 degrees CCW
                    $img->rotateimage("#FFF", -90);
                    break;
                }
            }
        }

        if ($this->_height == 'auto' && is_numeric($this->_width) && $img_width>$this->_width) { // resize only width
            $img->resizeimage($this->_width, 0, \Imagick::FILTER_LANCZOS, 1);
        } elseif ($this->_width == 'auto' && is_numeric($this->_height) && $img_height>$this->_height) {
            $img->resizeimage(0, $this->_height, \Imagick::FILTER_LANCZOS, 1);
        } elseif (is_numeric($this->_height) && is_numeric($this->_width)) {
            $img->resizeimage($this->_width, $this->_height, \Imagick::FILTER_LANCZOS, 1, 1);
        }

        #dpi
        $img->setImageUnits(\Imagick::RESOLUTION_PIXELSPERINCH);
        $img->setImageResolution($this->_dpi, $this->_dpi);

        #compression
        $img->setImageCompressionQuality($this->_compression);

        $name = (substr(md5(microtime()), 0, 4)).$name;
        $name = str_replace(' ', '', $name);
        try {
            $img->writeimage($dest . DS . $name);
        } catch (\Exception $e) {
            return false;
        }
        if ($create_thumb) {
            $this->createThumb($tmp_file, $dest.DS.'thumb_'.$name, $this->_thumb_width, $this->_thumb_height);
        }

        if ($multi) { // create 1920xX and 800xX
            $this->createThumb($tmp_file, $dest.DS.'hd_'.$name, 1920, 0);
            $this->createThumb($tmp_file, $dest.DS.'small_'.$name, 800, 0);
        }

        return $name;
    }

    /**
     * Deletes an image
     *
     * @param $name string Name of the image to delete (the image should be in the Img::dest folder)
     * @return bool
     */
    public function removeImage($name, $destination = false)
    {
        if (!$destination) {
            $dest = $this->dest;
        } else {
            $dest = $this->dest . DS . $destination;
        }

        if (file_exists($dest. DS . $name)) {
            $success = unlink($dest. DS . $name);
        } else {
            $success = false;
        }

        if ($success) {
            if (file_exists($dest. DS . 'thumb_'.$name)) {
                $success = unlink($dest.DS.'thumb_'.$name);
                return $success;
            }
        } else {
            return false;
        }
    }

    private function createThumb($tmp_file, $destination, $x, $y, $quality = 70)
    {
        try {
            $maxsize = max($x, $y);

            $image = new \Imagick($tmp_file);

            if ($image->getImageHeight() <= $image->getImageWidth()) {
                $image->resizeImage($maxsize, 0, \Imagick::FILTER_LANCZOS, 1);
            } else {
                $image->resizeImage(0, $maxsize, \Imagick::FILTER_LANCZOS, 1);
            }
            $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $image->setImageCompressionQuality($quality);
            $image->setImageResolution(72, 72);
            $image->stripImage();
            $image->writeImage($destination);
            $image->destroy();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $fileArray
     * @return bool
     */
    public function checkMimeType($fileArray)
    {
        if (empty($fileArray['type'])) {
            return false;
        }
        if (in_array($fileArray['type'], $this->validMimeTypes)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $fileArray
     * @return bool
     */
    public function checkSize($fileArray)
    {
        if ($this->maxFileSize == 0) {
            return true;
        } else {
            if ($fileArray['size'] <= $this->maxFileSize*1048576) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function setWidth($width)
    {
        $this->_width = $width;
    }

    public function setHeight($height)
    {
        $this->_height = $height;
    }

    public function setThumbWidth($width)
    {
        $this->_thumb_width = $width;
    }

    public function setThumbHeight($height)
    {
        $this->_thumb_height = $height;
    }
}
