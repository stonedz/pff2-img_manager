<?php

namespace pff\modules;
use pff\IConfigurableModule;

/**
 * Manages images
 */
class Img extends \pff\AModule implements IConfigurableModule{

    private $_resize, $_width, $_height, $_thumb_width, $_thumb_height, $dest;

    public function __construct($confFile = 'pff2-img_manager/module.conf.local.yaml'){
        $this->loadConfig($confFile);
        $this->dest = ROOT.DS.'app'.DS.'public'.DS.'files';
    }

    public function loadConfig($confFile) {
        $conf = $this->readConfig($confFile);
        $this->_resize       = $conf['moduleConf']['resize'];
        $this->_width        = $conf['moduleConf']['width'];
        $this->_height       = $conf['moduleConf']['height'];
        $this->_thumb_width  = $conf['moduleConf']['thumb_width'];
        $this->_thumb_height = $conf['moduleConf']['thumb_height'];
    }

    /**
     * @param $fileArray
     * @param bool $create_thumb
     * @return bool|string
     */
    public function saveImage($fileArray, $create_thumb = false) {

        $tmp_file = $fileArray['tmp_name'];
        $name     = $fileArray['name'];

        $img = new \Imagick($tmp_file);
        $img_width = $img->getimagewidth();
        $img_height = $img->getimageheight();
        if($this->_height == 'auto' && is_numeric($this->_width) && $img_width>$this->_width) { // resize only width
            $img->resizeimage($img_width, 0, \Imagick::FILTER_LANCZOS,1);
        }
        elseif($this->_width == 'auto' && is_numeric($this->_height) && $img_height>$this->_height) {
            $img->resizeimage(0, $img_height, \Imagick::FILTER_LANCZOS,1);
        }
        elseif(is_numeric($this->_height) && is_numeric($this->_width)) {
            $img->resizeimage($img_width, $img_height, \Imagick::FILTER_LANCZOS,1,1);
        }

        $name = (substr(md5(microtime()),0,4)).$name;
        try {
            $img->writeimage($this->dest . DS . $name);
        }
        catch(\Exception $e) {
            return false;
        }
        if($create_thumb) {
            $this->createThumb($tmp_file, $this->dest.DS.'thumb_'.$name,$this->_thumb_width,$this->_thumb_height);

        }
        return $name;
    }

    /**
     * Deletes an image
     *
     * @param $name string Name of the image to delete (the image should be in the Img::dest folder)
     * @return bool
     */
    public function removeImage($name) {
        $success = unlink($this->dest. DS . $name);
        if($success) {
            if(file_exists($this->dest. DS . 'thumb_'.$name)) {
                $success = unlink($this->dest.DS.'thumb_'.$name);
                return $success;
            }
        }
        else {
            return false;
        }
    }

    private function createThumb($tmp_file, $destination, $x, $y, $quality = 70) {
        try {
            $maxsize = max($x, $y);

            $image = new \Imagick($tmp_file);

            if($image->getImageHeight() <= $image->getImageWidth()){
                $image->resizeImage($maxsize,0,\Imagick::FILTER_LANCZOS,1);
            }else{
                $image->resizeImage(0,$maxsize,\Imagick::FILTER_LANCZOS,1);
            }
            $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $image->setImageCompressionQuality($quality);
            $image->setImageResolution(72,72);
            $image->stripImage();
            $image->writeImage($destination);
            $image->destroy();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }

    }
}
