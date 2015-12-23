<?php 

namespace VisoftBaseModule\Service;

use WebinoImageThumb\WebinoImageThumb;

class ImageService
{
	protected $thumbnailer;

    protected $xStartCrop;
    protected $yStartCrop;
    protected $widthCrop;
    protected $heightCrop;
    protected $widthCurrent;
    protected $heightCurrent;
    
    public function __construct(WebinoImageThumb $thumbnailer)
    {
        $this->thumbnailer = $thumbnailer;
    }

    public function saveImage($imagePath, $coordinaties, $entity)
    {
    	$this->exchangeArray($coordinaties);
    	$fileName = basename($imagePath);
    	var_dump($fileName);
    	die();
    }

    public function createPicture($originalPicture, $type, $postData)
    {
        $this->exchangeArray($postData);
        $explodedPath = explode('/', $originalPicture);
        $fileName = end($explodedPath);
        $fileNameKey = key($explodedPath);
        $thumb = $this->thumbnailer->create($originalPicture, $options = array(), $plugins = array());
        $currentDimantions = $thumb->getCurrentDimensions();
        $scale = $currentDimantions['width'] / $this->widthCurrent;
        $thumb->crop(
            $this->xStartCrop * $scale, 
            $this->yStartCrop * $scale, 
            $this->widthCrop * $scale, 
            $this->heightCrop * $scale
        );
        if($type === 'NewsletterPicture')
            $thumb->resize(700, 700);
        if($type === 'NewsletterLeftPictureRigthText')
            $thumb->resize(260, 260);
        if($type === 'NewsletterTwoPicturesTwoTexts')
            $thumb->resize(300, 300);
        if($type === 'L')
            $thumb->resize(600, 600);
        elseif($type === 'M') 
            $thumb->resize(300, 300);
        elseif($type === 'Mail')
            $thumb->resize(224, 224);
        elseif($type === 'XS') {
            $currentDimantions = $thumb->getCurrentDimensions();
            if($currentDimantions['height'] / $currentDimantions['width'] < 1)
                $thumb->cropFromCenter($currentDimantions['height'], $currentDimantions['height']);
            else 
                $thumb->cropFromCenter($currentDimantions['width'], $currentDimantions['width']);
            $thumb->resize(60, 60);
        } elseif ($type === 'S') {
            $currentDimantions = $thumb->getCurrentDimensions();
            if($currentDimantions['height'] / $currentDimantions['width'] < 1)
                $thumb->cropFromCenter($currentDimantions['height'], $currentDimantions['height']);
            else 
                $thumb->cropFromCenter($currentDimantions['width'], $currentDimantions['width']);
            $currentDimantions = $thumb->getCurrentDimensions();
            $thumb->cropFromCenter($currentDimantions['width'], ($currentDimantions['width'] * 2 / 3));
            $thumb->resize(224, 150);
        }
        $explodedPath[$fileNameKey] = 'Picture' . $type . '_' . $fileName;
        $newPicture = implode("/", $explodedPath);
        $thumb->save($newPicture);
        return $newPicture;
    }

    public function exchangeArray($data)
    {
        $this->xStartCrop = (isset($data['xStartCrop'])) ? $data['xStartCrop'] : null;
        $this->yStartCrop = (isset($data['yStartCrop'])) ? $data['yStartCrop'] : null;
        $this->widthCrop = (isset($data['widthCrop'])) ? $data['widthCrop'] : null;
        $this->heightCrop = (isset($data['heightCrop'])) ? $data['heightCrop'] : null;
        $this->widthCurrent = (isset($data['widthCurrent'])) ? $data['widthCurrent'] : null;
        $this->heightCurrent = (isset($data['heightCurrent'])) ? $data['heightCurrent'] : null;
    }
}