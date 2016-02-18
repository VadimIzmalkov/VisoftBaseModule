<?php

namespace VisoftBaseModule\Entity;

use Doctrine\ORM\Mapping as ORM,
	Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="visoft_base_images")
 */
class Image
{
	/**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    // xs - 60x60
    // s - 240x240
    // m - 480x480
    // l - 960x960 

    /**
     * @var string
     * @ORM\Column(name="original_size_path", type="string", length=255, nullable=true)
     */
    protected $originalSize;

    /**
     * @var string
     * @ORM\Column(name="xs_size_path", type="string", length=255, nullable=true)
     */
    protected $xsSize;

    /**
     * @var string
     * @ORM\Column(name="s_size_path", type="string", length=255, nullable=true)
     */
    protected $sSize;

    /**
     * @var string
     * @ORM\Column(name="m_size_path", type="string", length=255, nullable=true)
     */
    protected $mSize;

    /**
     * @var string
     * @ORM\Column(name="l_size_path", type="string", length=255, nullable=true)
     */
    protected $lSize;

    /**
     * @var integer
     * @ORM\Column(name="x_start_crop", type="integer", nullable=true)
     */
    protected $xStartCrop;

    /**
     * @var integer
     * @ORM\Column(name="y_start_crop", type="integer", nullable=true)
     */
    protected $yStartCrop;

    /**
     * @var integer
     * @ORM\Column(name="width_crop", type="integer", nullable=true)
     */
    protected $widthCrop;

    /**
     * @var integer
     * @ORM\Column(name="height_crop", type="integer", nullable=true)
     */
    protected $heightCrop;

    /**
     * @var integer
     * @ORM\Column(name="width_current", type="integer", nullable=true)
     */
    protected $widthCurrent;

    /**
     * @var integer
     * @ORM\Column(name="height_current", type="integer", nullable=true)
     */
    protected $heightCurrent;

    /**
     * @var \DateTime
     * @ORM\Column(name="date_time_created", type="datetime", nullable=true)
     */
    protected $createdAt;

    public function __construct() {
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setOriginalSize($originalSize) { $this->originalSize = $originalSize; }
    public function getOriginalSize() { return $this->originalSize; }

    public function setXsSize($xsSize) { $this->xsSize = $xsSize; }
    public function getXsSize() { return $this->xsSize; }

    public function setSSize($sSize) { $this->sSize = $sSize; }
    public function getSSize() { return $this->sSize; }

    public function setMSize($mSize) { $this->mSize = $mSize; }
    public function getMSize() { return $this->mSize; }

    public function setLSize($lSize) { $this->lSize = $lSize; }
    public function getLSize() { return $this->lSize; }

    public function setXStartCrop($xStartCrop) { $this->xStartCrop = $xStartCrop; }
    public function getXStartCrop() { return $this->xStartCrop; }

    public function setYStartCrop($yStartCrop) { $this->yStartCrop = $yStartCrop; }
    public function getYStartCrop() { return $this->yStartCrop; }

    public function setHeightCrop($heightCrop) { $this->heightCrop = $heightCrop; }
    public function getHeightCrop() { return $this->heightCrop; }

    public function setWidthCrop($widthCrop) { $this->widthCrop = $widthCrop; }
    public function getWidthCrop() { return $this->widthCrop; }

    public function setWidthCurrent($widthCurrent) { $this->widthCurrent = $widthCurrent; }
    public function getWidthCurrent() { return $this->widthCurrent; }

    public function setHeightCurrent($heightCurrent) { $this->heightCurrent = $heightCurrent; }
    public function getHeightCurrent() { return $this->heightCurrent; }
}