<?php 

namespace VisoftBaseModule\Controller;

use Zend\Mvc\Controller\AbstractActionController,
	Zend\View\Model\ViewModel;

abstract class AbstractCrudController extends AbstractActionController
{
	const CREATE_SUCCESS_MESSAGE = 'Entity created successfully!';

	protected $entityManager;
	protected $entityClass;
	protected $entityRepository;
	protected $uploadPath;

	//services
	protected $authenticationService = null;

	public function __construct($entityManager, $entityClass, $uploadPath) 
	{
		$this->entityClass = $entityClass;
		$this->entityManager = $entityManager;
		$this->entityRepository = $this->entityManager->getRepository($entityClass);
		$this->uploadPath = $uploadPath;
	}

	public function setAuthenticationService($authenticationService)
	{
		// TODO: add validation
		$this->authenticationService = $authenticationService;
	}

	public function getAuthenticationService()
	{
		if(is_null($this->authenticationService))
			return $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');
		else
			return $this->$authenticationService;
	}
}
