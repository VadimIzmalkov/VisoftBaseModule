<?php

namespace VisoftBaseModule\Service;

use Zend\Crypt\Password\Bcrypt;
use Doctrine\ORM\EntityManager;

class RegistrationService
{
	protected $entityManager;
	protected $authenticationService;
	protected $options;

	public function __construct(EntityManager $entityManager, $authenticationService, $options)
	{
		$this->entityManager = $entityManager;
		$this->authenticationService = $authenticationService;
		$this->options = $options;
	}

    public static function verifyHashedPassword($user, $passwordGiven)
    {
        $bcrypt = new Bcrypt(array('cost' => 10));
        return $bcrypt->verify($passwordGiven, $user->getPassword());
    }
    
    public static function encryptPassword($password)
    {
        $bcrypt = new Bcrypt(array('cost' => 10));
        return $bcrypt->create($password);
    }
}