<?php

namespace VisoftBaseModule\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class OAuth2Controller extends BaseController
{
	protected $oAuth2Client;
	protected $authenticationService;
	protected $moduleOptions;
	protected $redirects;

	public function __construct($authenticationService, $moduleOptions) 
	{
		$this->authenticationService = $authenticationService;
		$this->moduleOptions = $moduleOptions;
		$this->redirects = $this->moduleOptions->getRedirects();
	}

	public function oAuth2Action()
	{
		$cookie = $this->request->getCookie();
		$provider = $this->params()->fromRoute('provider');
		$code =$this->params()->fromQuery('code');
		switch ($provider) {
			case 'facebook':
				$this->oAuth2Client = $this->getServiceLocator()->get('VisoftBaseModule\Service\OAuth2\FacebookClient');
				break;
			case 'linkedin':
				$this->oAuth2Client = $this->getServiceLocator()->get('VisoftBaseModule\Service\OAuth2\LinkedInClient');
				break;
			default:
				throw new \Exception("Provider not defined", 1);
				break;
		}
		// setting scope
		// $this->oAuth2Client->getOptions()->setScope(['email']); SET IN CONFIG

		if (strlen($code) > 10) {
			// send request to facebook, generate token and save it to session
			$result = $this->oAuth2Client->generateToken($this->request);

			if($result)
				// token in session
                $token = $this->oAuth2Client->getSessionToken();
            else 
            	// last returned error (array)
                $token = $this->oAuth2Client->getError(); 

            // setting OAuth2 Client
            $adapter = $this->authenticationService->getAdapter();
            $adapter->setOAuth2Client($this->oAuth2Client);

            // authenticate
            $authenticationResult = $this->authenticationService->authenticate();

            if (!$authenticationResult->isValid()) {
                foreach ($authenticationResult->getMessages() as $message)
                    echo "$message\n";
                echo 'no valid';
            } else {
                echo 'valid';
                $identity = $authenticationResult->getIdentity();
                $this->authenticationService->getStorage()->write($identity);
            }

            // redirect
            if(isset($cookie->requestedUri)) {
            	// redirect to requested page
                $requestedUri = $cookie->requestedUri;
                $redirectUri = $this->getRequest()->getUri()->getScheme() . '://' . $this->getRequest()->getUri()->getHost() . $requestedUri;
                return $this->redirect()->toUrl($redirectUri);
            } else {
            	if($this->oAuth2Client->getNewUserFlag())
            		$redirectRoute = $this->redirects['sign-up']['route'];
            	else 
            		$redirectRoute = $this->redirects['sign-in']['route'];
                return $this->redirect()->toRoute($redirectRoute);
            }
		}
	}
}