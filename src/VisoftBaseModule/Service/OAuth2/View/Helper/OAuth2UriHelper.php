<?php

namespace VisoftBaseModule\Service\OAuth2\View\Helper;

class OAuth2UriHelper extends \Zend\View\Helper\AbstractHelper implements \Zend\ServiceManager\ServiceLocatorAwareInterface
{
	protected $facebookClient;
	protected $linkedInClient;

	public function __construct($socialClients)
	{
		$this->facebookClient = $socialClients['facebook'];
		$this->linkedInClient = $socialClients['linkedin'];
	}

	public function __invoke($provider)
	{
		switch ($provider) {
			case 'facebook':
				return $this->facebookClient->getUrl();
			case 'linkedin':
				return $this->linkedInClient->getUrl();
			default:
				# code...
				break;
		}
	}

	public function setServiceLocator(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator) 
	{
	   $this->serviceLocator = $serviceLocator;
	}

	public function getServiceLocator() 
	{
		return $this->serviceLocator;
	}
}