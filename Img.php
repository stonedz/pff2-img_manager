<?php

namespace pff\modules;
use pff\IConfigurableModule;

/**
 * Manages images
 */
class Img extends \pff\AModule implements IConfigurableModule{

    private $_resize, $_width, $_height, $_thumb_width, $_thumb_height;

    public function __construct($confFile = 'pff2-img_manager/module.conf.local.yaml'){
        $this->loadConfig($confFile);
    }

    public function loadConfig($confFile) {
        $conf = $this->readConfig($confFile);
        $this->_resize       = $conf['moduleConf']['resize'];
        $this->_width        = $conf['moduleConf']['width'];
        $this->_height       = $conf['moduleConf']['height'];
        $this->_thumb_width  = $conf['moduleConf']['thumb_width'];
        $this->_thumb_height = $conf['moduleConf']['thumb_height'];


//        try {
//            foreach ($conf['moduleConf']['activeLoggers'] as $logger) {
//                $tmpClass         = new \ReflectionClass('\\pff\\modules\\' . (string)$logger['class']);
//                $this->_loggers[] = $tmpClass->newInstance();
//            }
//        } catch (\ReflectionException $e) {
//            throw new \pff\modules\LoggerException('Logger creation failed: ' . $e->getMessage());
//        }
    }

    /**
     * Saves an Img uploaded
     *
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

        $img->writeimage(ROOT.DS.'app'.DS.'public'.DS.'files'.$name);

        if($create_thumb) {
            $this->createThumb($tmp_file,22,22,22,22);

        }
        return true;
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
