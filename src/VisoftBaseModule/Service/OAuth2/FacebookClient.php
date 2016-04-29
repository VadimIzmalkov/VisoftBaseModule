<?php

namespace VisoftBaseModule\Service\OAuth2;

use VisoftBaseModule\Entity;

class FacebookClient extends AbstractOAuth2Client
{
	protected $providerName = 'facebook';
    
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
            // $this->httpClient
            //     ->setUri($this->options->getTokenUri())
            //     // ->setUri('https://graph.facebook.com/v2.0/oauth/access_token')
            //     ->setMethod(\Zend\Http\PhpEnvironment\Request::METHOD_POST)
            //     ->setParameterPost([
            //         'code'          => $request->getQuery('code'),
            //         'client_id'     => $this->options->getClientId(),
            //         'client_secret' => $this->options->getClientSecret(),
            //         'redirect_uri'  => $this->options->getRedirectUri()
            //     ]);
            $url = 'https://graph.facebook.com/v2.1/oauth/access_token?' 
                . 'client_id='      . $this->options->getClientId()
                . '&redirect_uri='  . urlencode($this->options->getRedirectUri())
                . '&client_secret=' . $this->options->getClientSecret()
                . '&code='          . $request->getQuery('code');
            // $this->httpClient
            //     ->setUri($url)
            //     ->setMethod(\Zend\Http\PhpEnvironment\Request::METHOD_GET);
            // $responseContent = $this->httpClient->send()->getContent();
            $curl = curl_init();
            $timeout = 0;
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
            $responseContent = curl_exec($curl);
            curl_close($curl);
            
            parse_str($responseContent, $token);

            if(is_array($token) AND isset($token['access_token']) AND $token['expires'] > 0) {
                $this->session->token = (object)$token;
                return true;
            } else {
                try {
                    $error = \Zend\Json\Decoder::decode($responseContent);
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

    public function getUrl()
    {
        $url = $this->options->getAuthUri() . '?'
            . 'redirect_uri='  . urlencode($this->options->getRedirectUri())
            . '&client_id='    . $this->options->getClientId()
            . '&state='        . $this->generateState()
            . $this->getScope(',');
        return $url;
    }

    public function getInfoUri()
    {
        return $this->options->getInfoUri() . '?access_token=' . $this->session->token->access_token;
    }

    public function getProviderAvatar($oAuth2ProfileInfo)
    {
        $profileImageUrl = "https://graph.facebook.com/" . $oAuth2ProfileInfo['id'] . "/picture";
        $avatar = new Entity\Image();
        $avatar->setOriginalSize($profileImageUrl . "?width=1000&height=1000");
        $avatar->setXsSize($profileImageUrl . "?width=64&height=64");
        $avatar->setSSize($profileImageUrl . "?width=224&height=224");
        $avatar->setMSize($profileImageUrl . "?width=512&height=512");
        $avatar->setLSize($profileImageUrl . "?width=940&height=940");
        $this->entityManager->persist($avatar);
        return $avatar;
    }
}