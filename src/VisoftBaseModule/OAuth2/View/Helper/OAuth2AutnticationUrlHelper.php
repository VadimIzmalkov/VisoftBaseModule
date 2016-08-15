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

	public function __invoke($provider, $addQueryFromUrl = false)
	{
		$fromUrl = $addQueryFromUrl ? $this->view->serverUrl(true) : null;

		switch ($provider) {
			case 'facebook':
				return $this->facebookProvider->getAuthenticationUrl($fromUrl);

			case 'linkedin':
				return $this->linkedinProvider->getAuthenticationUrl($fromUrl);

			default:
				# code...
				break;
		}
	}
}