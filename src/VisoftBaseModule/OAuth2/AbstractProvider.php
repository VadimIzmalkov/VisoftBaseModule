<?php

namespace VisoftBaseModule\OAuth2;

abstract class AbstractProvider 
{
	protected $options;
	protected $session;
	protected $entityManager;
	protected $userService;

	protected $httpClient;

	// grant
	public $authorizationCode = null;
	public $providerState;

	const PROVIDER_NAME = 'abstractProvider';

	abstract public function getAuthenticationUrl();
	abstract public function getAvatar($providerId);

	abstract protected function generateAccessToken();
	abstract protected function getUserProfileInfoUri($accessToken);
    // abstract public function getIdentity();

	public function __construct($options, $entityManager, $userService)
	{
		$this->options = $options;
		$this->entityManager = $entityManager;
        $this->userService = $userService;
		$this->session = new \Zend\Session\Container('OAuth2_' . get_class($this));

		// setup HTTP client
		$this->httpClient = new \Zend\Http\Client(null, [
            // 'timeout' => 30, 
            'adapter' => '\Zend\Http\Client\Adapter\Curl', 
            'curloptions' => [
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => 1,
            ],
        ]);
	}

	protected function generateState() 
	{
        $this->session->state = md5(microtime().'-'.get_class($this));
        return $this->session->state;
    }

    public function getScope($glue = ' ') 
    {
        if(is_array($this->options->getScope()) AND count($this->options->getScope()) > 0) {
            $str = urlencode(implode($glue, array_unique($this->options->getScope())));
            return '&scope=' . $str;
        } else {
            return '';
        }
    }

    public function getProviderName()
    {
        return static::PROVIDER_NAME;
    }
}
