<?php 

namespace VisoftBaseModule\Service\OAuth2;

use VisoftBaseModule\Entity;

class LinkedInClient extends AbstractOAuth2Client
{
	protected $providerName = 'linkedin';

    public function __construct($entityManager, $userService)
    {
        parent::__construct($entityManager, $userService);
    } 

    public function generateToken(\Zend\Http\PhpEnvironment\Request $request) 
    {
    	if(isset($this->session->token)) { 
            return true;
        } elseif(
        	strlen($this->session->state) > 0 
        	AND $this->session->state == $request->getQuery('state') 
        	AND strlen($request->getQuery('code')) > 5
        ) {
        	$this->httpClient
        		->setUri($this->options->getTokenUri())
        		->setMethod(\Zend\Http\PhpEnvironment\Request::METHOD_POST)
        		// ->setHeaders(['Content-Type: application/x-www-form-urlencoded; charset=UTF-8'])
        		->setParameterPost([
        			'grant_type' 	=> 'authorization_code',
        			'code'          => $request->getQuery('code'),
        			'redirect_uri'	=> $this->options->getRedirectUri(),
        			'client_id'		=> $this->options->getClientId(),
        			'client_secret' => $this->options->getClientSecret()
        		]);
        	$response = $this->httpClient->send();
        	$token = \Zend\Json\Decoder::decode($response->getBody());
        	var_dump($token);
        	// die('ff');
   //      	var_dump($response);
   //      	var_dump(json_decode($response->getBody(), true));
   //      	die('fdfd');
        	
   //      	$timeout = 0;
			// $postData = http_build_query([
   //  			'grant_type' => 'authorization_code',
   //  			'code' => $request->getQuery('code'),
   //  			'redirect_uri' => $this->options->getRedirectUri(),
   //  			'client_id' => $this->options->getClientId(),
   //  			'client_secret' => $this->options->getClientSecret()
   //      	]);
        	
   //      	$curl = curl_init();
   //      	curl_setopt($curl, CURLOPT_URL, $this->options->getTokenUri());
   //      	curl_setopt($curl, CURLOPT_POST, 1);
   //      	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
   //      	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
   //      	curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        	
   //      	$responseContent = curl_exec($curl);

        	if(is_object($token) AND isset($token->access_token) AND ($token->expires_in > 0)) {
                $this->session->token = (object)$token;
                return true;
            } else {
                try {
                    $error = \Zend\Json\Decoder::decode($response);
                    $this->error = array(
                        'internal-error' => 'Facebook settings error.',
                        'message' => $error->error->message,
                        'type' => $error->error->type,
                        'code' => $error->error->code
                    );
                } catch(\Zend\Json\Exception\RuntimeException $e) {
                    $this->error = $token;
                    $this->error['internal-error'] = 'Unknown error.';         
                }
                return false;
            }
        } else {
            $this->error = array(
                'internal-error'=> 'State error, request variables do not match the session variables.',
                'session-state' => $this->session->state,
                'request-state' => $request->getQuery('state'),
                'code'          => $request->getQuery('code')
            );
            return false;
        }
    }

    public function getInfo()
    {
        // if(is_object($this->session->info)) {
        //     var_dump($this->session);
        //     return $this->session->info;
        // } elseif(isset($this->session->token->access_token)) {
    		// Zend\Http\Client\Adapter\Curl - doesn't work with linkedin url parameters
    		// https://github.com/zendframework/zf2/issues/6153
    		$curl = curl_init();
    		curl_setopt($curl, CURLOPT_URL, $this->getInfoUri());
    		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    		$responseContent = curl_exec($curl);
    		curl_close($curl);

    		if(strlen(trim($responseContent)) > 0) {
                $infoObject = \Zend\Json\Decoder::decode($responseContent);
                $infoObject->email = $infoObject->emailAddress;
                unset($infoObject->emailAddress);
                $infoObject->first_name = $infoObject->firstName;
                unset($infoObject->firstName);
                $infoObject->last_name = $infoObject->lastName;
                unset($infoObject->lastName);
                $this->session->info = $infoObject;
                return $this->session->info;
            } else {
                $this->error = array('internal-error' => 'Get info return value is empty.');
                return false;
            }
        // } else {
        //     $this->error = array('internal-error' => 'Session access token not found.');
        //     return false;
        // }
    }

    public function getUrl()
    {
        return $this->options->getAuthUri() . '?'
        	. 'response_type=code'
            . '&redirect_uri='  	. $this->options->getRedirectUri()
            . '&client_id='    		. $this->options->getClientId()
            . '&state='        		. $this->generateState()
            . $this->getScope(',');
    }

    public function getInfoUri()
    {
    	return $this->options->getInfoUri() 
    		. ':(id,firstName,lastName,email-address,picture-url,picture-urls::(original))'
    		. '?format=json'
    		. '&oauth2_access_token=' . $this->session->token->access_token;
    }

    public function getProviderAvatar($oAuth2ProfileInfo)
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