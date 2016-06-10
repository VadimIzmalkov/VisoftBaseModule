<?php

namespace VisoftBaseModule\Adapter;

use Zend\Authentication\Result as AuthenticationResult;

class AuthenticationAdapter extends \DoctrineModule\Authentication\Adapter\ObjectRepository
{
	private $oAuth2Client = null;

    public function authenticate()
    {
    	if(is_object($this->oAuth2Client)) {
    		// authentication with social networks
    		$identity = $this->oAuth2Client->getIdentity();
    		if (!$identity) {
	            $this->authenticationResultInfo['code']       = AuthenticationResult::FAILURE_CREDENTIAL_INVALID;
	            $this->authenticationResultInfo['messages'][] = 'Authentication with OAuth2 protocol failed';

	            return $this->createAuthenticationResult();
	        }
	        return new AuthenticationResult(AuthenticationResult::SUCCESS, $identity);
    	} else {
    		// authentication with email and password
            $this->setup();
            $options  = $this->options;
            $identity = $options
                ->getObjectRepository()
                ->findOneBy(array($options->getIdentityProperty() => $this->identity));

            if (!$identity) {
                $this->authenticationResultInfo['code']       = AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND;
                $this->authenticationResultInfo['messages'][] = 'A record with the supplied identity could not be found.';

                return $this->createAuthenticationResult();
            } elseif($identity->getActive() !== true) {
                // user not active or not confirmed email
                $this->authenticationResultInfo['code']       = AuthenticationResult::FAILURE_UNCATEGORIZED;
                $this->authenticationResultInfo['messages'][] = 'A record is not active or not confirmed email';

                return $this->createAuthenticationResult();
            }

            $authResult = $this->validateIdentity($identity);

            return $authResult;
    		// return parent::authenticate();
    	}
    }

    public function setOAuth2Client($oAuth2Client)
    {
        $this->oAuth2Client = $oAuth2Client;
    }
}