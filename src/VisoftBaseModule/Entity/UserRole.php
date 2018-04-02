<?php

namespace VisoftBaseModule\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Role
 * @ORM\Entity
 * @ORM\Table(name="visoft_base_user_roles")
 *
 */
class UserRole
{
    const ID_ADMINISTRATOR      = 1;
    const ID_REPRESENTATIVE     = 2;
    const ID_MEMBER             = 3;
    const ID_SUBSCRIBER         = 4;
    const ID_MEMBER_PREMIUM     = 5;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="name_displayed", type="string", length=255, nullable=false)
     */
    protected $nameDisplayed;
    
    public function getId() { return $this->id; }

    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; return $this; }

    public function getNameDisplayed() { return $this->nameDisplayed; }
    public function setNameDisplayed($nameDisplayed) { $this->nameDisplayed = $nameDisplayed; return $this; }

}