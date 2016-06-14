<?php

namespace VisoftBaseModule\OAuth2;

class FacebookProvider extends AbstractProvider
{
	const PROVIDER_NAME = 'facebook';

	public function __construct($options, $entityManager, $userService)
	{
		parent::__construct($options, $entityManager, $userService);
	}

	protected function generateAccessToken()
	{
        if(($this->providerState !== $this->session->state) || empty($this->session->state)) {
            exit('Invalid state');
        }

		$curl = curl_init();
	    $timeout = 0;
	    curl_setopt($curl, CURLOPT_URL, $this->getAccessTokenUrl());
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
	    $responseContent = curl_exec($curl);
	    curl_close($curl);
	    
	    // parse_str($responseContent, $parsedContent);

        $response = \Zend\Json\Decoder::decode($responseContent);

	    return $response->access_token;
	}

    public function getUserProfileInfo()
    {
        // generate access token using the authorization code grant
        $accessToken = $this->generateAccessToken($this->authorizationCode, $this->providerState);

        // get URi for user's profile information using access token
        $response = $this->httpClient->resetParameters(true)
            ->setMethod(\Zend\Http\PhpEnvironment\Request::METHOD_GET)
            ->setUri($this->getUserProfileInfoUri($accessToken))
            ->send();

        $userProfileInfoObject = \Zend\Json\Decoder::decode($response->getBody());

        // return $this->mapUserProfileInfoObject2Array($userProfileInfoObject);
        $userProfileInfo['email'] = $userProfileInfoObject->email;
        $userProfileInfo['fullName'] = $userProfileInfoObject->first_name . " " . $userProfileInfoObject->last_name;
        $userProfileInfo['providerId'] = $userProfileInfoObject->id;

        return $userProfileInfo;
    }

  //   protected function mapUserProfileInfoObject2Array($userProfileInfoObject)
  //   {
  //   	$userProfileInfo['email'] = $userProfileInfoObject->email;
  //   	$userProfileInfo['fullName'] = $userProfileInfoObject->first_name . " " . $userProfileInfoObject->last_name;
  //   	$userProfileInfo['providerId'] = $userProfileInfoObject->id;

		// return $userProfileInfo;
  //   }

    public function getAuthenticationUrl()
    {
        $url = $this->options->getAuthUri() . '?'
            . 'redirect_uri='  . urlencode($this->options->getRedirectUri())
            . '&client_id='    . $this->options->getClientId()
            . '&state='        . $this->generateState()
            . $this->getScope(',');
        return $url;
    }

    protected function getAccessTokenUrl()
    {
        $url = 'https://graph.facebook.com/v2.3/oauth/access_token?' 
            . 'client_id='      . $this->options->getClientId()
            . '&redirect_uri='  . urlencode($this->options->getRedirectUri())
            . '&client_secret=' . $this->options->getClientSecret()
            . '&code='          . $this->authorizationCode;
        return $url;
    }

    protected function getUserProfileInfoUri($accessToken)
    {
        return $this->options->getInfoUri() . '?access_token=' . $accessToken;
    }

    public function getAvatar($providerId)
    {
        $profileImageUrl = "https://graph.facebook.com/" . $providerId . "/picture";
        $avatar = new \VisoftBaseModule\Entity\Image();
        $avatar->setOriginalSize($profileImageUrl . "?width=1000&height=1000");
        $avatar->setXsSize($profileImageUrl . "?width=64&height=64");
        $avatar->setSSize($profileImageUrl . "?width=224&height=224");
        $avatar->setMSize($profileImageUrl . "?width=512&height=512");
        $avatar->setLSize($profileImageUrl . "?width=940&height=940");
        $this->entityManager->persist($avatar);
        return $avatar;
    }
}