<?php

namespace VisoftBaseModule\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Role
 * @ORM\Entity
 * @ORM\Table(name="user_roles")
 *
 */
class UserRole
{
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
    
    public function getId() { return $this->id; }

    public function getName() { return $this->name; }
    public function setName($name) {
        $this->name = $name;
        return $this;
    }
}
