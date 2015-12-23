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
    // s - 120x120
    // m - 240x240
    // l - 600x600 

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

    public function getId() { return $this->id; }

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
}