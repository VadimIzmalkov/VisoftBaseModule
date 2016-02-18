<?php

namespace VisoftBaseModule\Service;

use Doctrine\ORM\EntityManager;

use VisoftBaseModule\Options\ModuleOptions;

class UserService implements UserServiceInterface
{
	protected $entityManager;
	protected $moduleOptions;

	public function __construct(
		EntityManager $entityManager,
		ModuleOptions $moduleOptions
	)
	{
		$this->entityManager = $entityManager;
		$this->moduleOptions = $moduleOptions;
	}

	public function getOptions()
	{
		return $this->moduleOptions;
	}
}