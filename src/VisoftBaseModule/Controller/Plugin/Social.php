<?php
namespace VisoftBaseModule\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Social extends AbstractPlugin
{
	protected $facebookClient;
	protected $linkedInClient;

	public function __construct($socialClients)
	{
		// var_dump($socialClients['facebook']->getUrl());
		// var_dump($socialClients['linkedin']->getUrl());
		// die('ffff1');
		$this->facebookClient = $socialClients['facebook'];
		$this->linkedInClient = $socialClients['linkedin'];
	}

	public function getSignInUrl($provider)
	{
		// var_dump($provider);
		// var_dump($this->facebookClient->getUrl());
		// die('ffff1');
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
}
