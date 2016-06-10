<?php

namespace VisoftBaseModule\OAuth2\View\Helper;

/**
 * Generate URI for each OAuth2 providers
 */
class OAuth2AutnticationUrlHelper extends \Zend\View\Helper\AbstractHelper
{
	protected $facebookProvider;
	protected $linkedinProvider;

	public function __construct($socialProviders)
	{
		$this->facebookProvider = $socialProviders['facebook'];
		$this->linkedinProvider = $socialProviders['linkedin'];
	}

	public function __invoke($provider)
	{
		switch ($provider) {
			case 'facebook':
				return $this->facebookProvider->getAuthenticationUrl();
			case 'linkedin':
				return $this->linkedinProvider->getAuthenticationUrl();
				// return null;
			default:
				# code...
				break;
		}
	}
}