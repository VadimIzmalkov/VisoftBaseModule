<?php

namespace VisoftBaseModule\Options;

use Zend\Stdlib\AbstractOptions;

class ModuleOptions extends AbstractOptions
{
	// roles
	// protected $roleGuest = null;
	// protected $roleSuperUser = 1;
	// protected $roleUser = 2;
	// protected $roleMember = 3;
	protected $roleSubscriberId = 4;

    public function __construct($options)
    {
    	parent::__construct($options);
    }

    public function getRoleSubscriberId() { return $this->roleSubscriberId; }
    public function setRoleSubscriberId($roleSubscriberId) { 
    	$this->roleSubscriberId = $roleSubscriberId;
        return $this;
    }
}