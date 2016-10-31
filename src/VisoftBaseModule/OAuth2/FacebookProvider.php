<?php

namespace VisoftBaseModule\OAuth2;

class FacebookProvider extends AbstractProvider
{
	const PROVIDER_NAME = 'facebook';

    private $logger;
    private $facebookSDK;

	public function __construct($options, $entityManager, $userService)
	{
		parent::__construct($options, $entityManager, $userService);

        // init ZF2 logger
        $logFileDir = getcwd() . '/data/VisoftBaseModule/log/';
        $logFilePath = $logFileDir . 'oauth2-provider-' . static::PROVIDER_NAME . '.log';
        \VisoftBaseModule\Controller\Plugin\AccessoryPlugin::checkDir($logFileDir);
        $this->logger = new \Zend\Log\Logger;
        $writer = new \Zend\Log\Writer\Stream($logFilePath);
        $this->logger->addWriter($writer);

        // init Facebook SDK
        $this->facebookSDK = new \Facebook\Facebook([
            'app_id' => $options->getClientId(), // Replace {app-id} with your app id
            'app_secret' => $options->getClientSecret(),
            'default_graph_version' => 'v2.6',
        ]);
	}

	protected function generateAccessToken()
	{
        $helper = $this->facebookSDK->getRedirectLoginHelper();

        try {
            $accessToken = $helper->getAccessToken();
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        if (! isset($accessToken)) {
            if ($helper->getError()) {
                header('HTTP/1.0 401 Unauthorized');
                echo "Error: " . $helper->getError() . "\n";
                echo "Error Code: " . $helper->getErrorCode() . "\n";
                echo "Error Reason: " . $helper->getErrorReason() . "\n";
                echo "Error Description: " . $helper->getErrorDescription() . "\n";
            } else {
                header('HTTP/1.0 400 Bad Request');
                echo 'Bad request';
            }
            exit;
        }

        // Logged in
        // echo '<h3>Access Token</h3>';
        // var_dump($accessToken->getValue());

        // The OAuth 2.0 client handler helps us manage access tokens
        $oAuth2Client = $this->facebookSDK->getOAuth2Client();

        // Get the access token metadata from /debug_token
        $tokenMetadata = $oAuth2Client->debugToken($accessToken);
        // echo '<h3>Metadata</h3>';
        // var_dump($tokenMetadata);

        // Validation (these will throw FacebookSDKException's when they fail)
        $tokenMetadata->validateAppId($this->options->getClientId()); // Replace {app-id} with your app id
        // If you know the user ID this access token belongs to, you can validate it here
        //$tokenMetadata->validateUserId('123');
        $tokenMetadata->validateExpiration();

        if (! $accessToken->isLongLived()) {
            // Exchanges a short-lived access token for a long-lived one
            try {
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            } catch (Facebook\Exceptions\FacebookSDKException $e) {
                echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
                exit;
            }

            // echo '<h3>Long-lived</h3>';
            // var_dump($accessToken->getValue());
        }

        return $accessToken->getValue();


  //       if(($this->providerState !== $this->session->state) || empty($this->session->state)) {
  //           exit('Invalid state');
  //       }

  //       if(isset($this->session->access_token))
  //           return $this->session->access_token;

		// $curl = curl_init();
	 //    $timeout = 0;
	 //    curl_setopt($curl, CURLOPT_URL, $this->getAccessTokenUrl());
	 //    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	 //    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
	 //    $responseContent = curl_exec($curl);
	 //    curl_close($curl);

  //       // log access token
  //       $this->logger->info('Access token response: ' . $responseContent);

  //       $response = \Zend\Json\Decoder::decode($responseContent);

  //       $this->session->access_token = $response->access_token;

	 //    return $this->session->access_token;
	}

    public function getUserProfileInfo()
    {
        // generate access token using the authorization code grant
        $accessToken = $this->generateAccessToken($this->authorizationCode, $this->providerState);

        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = $this->facebookSDK->get('/me?fields=id,name,email', $accessToken);
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        $user = $response->getGraphUser();

        $userProfileInfo['email'] = $user['email'];
        $userProfileInfo['fullName'] = $user['name'];
        $userProfileInfo['providerId'] = $user['id'];

        // generate access token using the authorization code grant
        // $accessToken = $this->generateAccessToken($this->authorizationCode, $this->providerState);

        // // get URi for user's profile information using access token
        // $response = $this->httpClient->resetParameters(true)
        //     ->setMethod(\Zend\Http\PhpEnvironment\Request::METHOD_GET)
        //     ->setUri($this->getUserProfileInfoUri($accessToken))
        //     ->send();

        // // log access token
        // $this->logger->info('User profile response: ' . $response->getBody());

        // $userProfileInfoObject = \Zend\Json\Decoder::decode($response->getBody());

        // return $this->mapUserProfileInfoObject2Array($userProfileInfoObject);
        // $userProfileInfo['email'] = $userProfileInfoObject->email;
        // $userProfileInfo['fullName'] = $userProfileInfoObject->first_name . " " . $userProfileInfoObject->last_name;
        // $userProfileInfo['providerId'] = $userProfileInfoObject->id;

        return $userProfileInfo;
    }

  //   protected function mapUserProfileInfoObject2Array($userProfileInfoObject)
  //   {
  //   	$userProfileInfo['email'] = $userProfileInfoObject->email;
  //   	$userProfileInfo['fullName'] = $userProfileInfoObject->first_name . " " . $userProfileInfoObject->last_name;
  //   	$userProfileInfo['providerId'] = $userProfileInfoObject->id;

		// return $userProfileInfo;
  //   }

    public function getAuthenticationUrl($referCode = null)
    {
        $redirectLoginHelper = $this->facebookSDK->getRedirectLoginHelper();

        $permissions = $this->options->getScope();//['email']; // Optional permissions
        $callbackUri = $this->options->getRedirectUri();
        if(!empty($referCode)) {
            // add / if not added:
            $callbackUri = rtrim($callbackUri, '/') . '/';
            
            $callbackUri .= $referCode . '/';
        }
        // if(!empty($query)) {
        //     $callbackUri .= '?';
        //     $first = true;
        //     foreach ($query as $parameter => $value) {
        //         if(!$first) 
        //             $callbackUri .= '&';
        //         $callbackUri .= $parameter . '=' . $value;
        //         $first = false;
        //     }
        // }

        // $callbackUri = is_null($fromUrl) ? $this->options->getRedirectUri() : $this->options->getRedirectUri() . '?from=' . $fromUrl;
        $loginUrl = $redirectLoginHelper->getLoginUrl($callbackUri, $permissions);

        return $loginUrl;

        // $url = $this->options->getAuthUri() . '?'
        //     . 'redirect_uri='  . urlencode($this->options->getRedirectUri())
        //     . '&client_id='    . $this->options->getClientId()
        //     . '&state='        . $this->generateState()
        //     . $this->getScope(',');
        // return $url;
    }

    protected function getAccessTokenUrl()
    {
        $url = 'https://graph.facebook.com/v2.6/oauth/access_token?' 
            . 'client_id='      . $this->options->getClientId()
            . '&redirect_uri='  . urlencode($this->options->getRedirectUri())
            . '&client_secret=' . $this->options->getClientSecret()
            . '&code='          . $this->authorizationCode;
        return $url;
    }

    protected function getUserProfileInfoUri($accessToken)
    {
        // TODO: Should be added 'fields=id,name,email' to URi
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