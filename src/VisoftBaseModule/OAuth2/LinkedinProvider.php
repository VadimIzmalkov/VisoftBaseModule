<?php

namespace VisoftBaseModule\OAuth2;

class LinkedinProvider extends AbstractProvider
{
	const PROVIDER_NAME = 'linkedin';

	public function __construct($options, $entityManager, $userService)
	{
		parent::__construct($options, $entityManager, $userService);
	}

	protected function generateAccessToken()
	{
        if(($this->providerState !== $this->session->state) || empty($this->session->state)) {
            exit('Invalid state');
        }

    	$this->httpClient
    		->setUri($this->options->getTokenUri())
    		->setMethod(\Zend\Http\PhpEnvironment\Request::METHOD_POST)
    		// ->setHeaders(['Content-Type: application/x-www-form-urlencoded; charset=UTF-8'])
    		->setParameterPost([
    			'grant_type' 	=> 'authorization_code',
    			'code'          => $this->authorizationCode,
    			'redirect_uri'	=> $this->redirectUri, //'http://frydayoffline.net/authentication/o-auth2/linkedin/apply-to-become-a-representative/', //$this->options->getRedirectUri(),
    			'client_id'		=> $this->options->getClientId(),
    			'client_secret' => $this->options->getClientSecret()
    		]);
    	$response = $this->httpClient->send();
    	$token = \Zend\Json\Decoder::decode($response->getBody());

        // var_dump($token);
        // die('123');

    	return $token->access_token;
	}

    public function getUserProfileInfo()
    {
        // generate access token using the authorization code grant
        $accessToken = $this->generateAccessToken($this->authorizationCode, $this->providerState);

        // get URi for user's profile information using access token
        $userProfileInfoUri = $this->getUserProfileInfoUri($accessToken);

        // send request
        // $response = $this->httpClient->resetParameters(true)
        //     ->setMethod(\Zend\Http\PhpEnvironment\Request::METHOD_GET)
        //     ->setUri($userProfileInfoUri)
        //     ->send();
        $curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $userProfileInfoUri);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($curl);
		curl_close($curl);

        $userProfileInfoObject = \Zend\Json\Decoder::decode($response);

        // return $this->mapUserProfileInfoObject2Array($userProfileInfoObject);
        $userProfileInfo['email'] = $userProfileInfoObject->emailAddress;
        $userProfileInfo['fullName'] = $userProfileInfoObject->firstName . " " . $userProfileInfoObject->lastName;
        $userProfileInfo['providerId'] = $userProfileInfoObject->id;

        return $userProfileInfo;
    }

    public function getAuthenticationUrl($referCode = null)
    {
        $callbackUri = $this->options->getRedirectUri();
        if(!empty($referCode)) {
            $callbackUri = rtrim($callbackUri, '/') . '/';
            $callbackUri .= $referCode . '/';
        }
        // var_dump($redirectUri);
        // if(!empty($query)) {
        //     $redirectUri .= '?';
        //     $first = true;
        //     foreach ($query as $parameter => $value) {
        //         if(!$first) 
        //             $redirectUri .= '&';
        //         $redirectUri .= $parameter . '=' . $value;
        //         $first = false;
        //     }
        // }
        // var_dump($callbackUri);
        // var_dump(urlencode($redirectUri));
        // die('123');

        $url = $this->options->getAuthUri() . '?'
        	. 'response_type=code'
            . '&client_id='    		. $this->options->getClientId()
            . '&state='        		. $this->generateState()
            . $this->getScope(',')
            . '&redirect_uri='      . $callbackUri;

        // var_dump($url);
        // die('123');        
        return $url;
    }

    public function getUserProfileInfoUri($accessToken)
    {
    	$url = $this->options->getInfoUri() 
    		. ':(id,firstName,lastName,email-address,picture-url,picture-urls::(original))'
    		. '?format=json'
    		. '&oauth2_access_token=' . $accessToken;
    		// . '&oauth2_access_token=' . $this->session->token->access_token;
    	return $url;
    }

    public function getAvatar($oAuth2ProfileInfo)
    {
        $originalPictureUrl = $oAuth2ProfileInfo['pictureUrls']->values[0];
        $smallPictureUrl = $oAuth2ProfileInfo['pictureUrl'];
        $avatar = new Entity\Image();
        $avatar->setOriginalSize($originalPictureUrl);
        $avatar->setXsSize($smallPictureUrl);
        $avatar->setSSize($originalPictureUrl);
        $avatar->setMSize($originalPictureUrl);
        $avatar->setLSize($originalPictureUrl);
        $this->entityManager->persist($avatar);
        return $avatar;
    }
}