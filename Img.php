<?php

namespace pff\modules;
use pff\IConfigurableModule;

/**
 * Manages images
 */
class Img extends \pff\AModule implements IConfigurableModule{


    public function __construct($confFile = 'pff2-img_manager/module.conf.local.yaml'){
        $this->loadConfig($confFile);
    }

    public function loadConfig($confFile) {
        $conf = $this->readConfig($confFile);
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
     */
    public function saveImage($fileArray, $create_thumb = false) {

        $tmp_file = $fileArray['tmp_name'];
        echo 'OK IMMAGINE';
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
