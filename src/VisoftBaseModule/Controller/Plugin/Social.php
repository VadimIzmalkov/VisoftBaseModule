<?php
namespace VisoftBaseModule\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

// TODO: Rename to OAuth2UriLugin!!!!! and do __invoke
class Social extends AbstractPlugin
{
	protected $facebookClient;
	protected $linkedInClient;

	public function __construct($socialClients)
	{
		$this->facebookClient = $socialClients['facebook'];
		$this->linkedInClient = $socialClients['linkedin'];
	}

	public function getSignInUrl($provider)
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
}
