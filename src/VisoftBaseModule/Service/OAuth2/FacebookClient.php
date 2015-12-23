<?php

namespace VisoftBaseModule\Service\OAuth2;

use Zend\Http\PhpEnvironment\Request;

use VisoftBaseModule\Entity;

class FacebookClient extends AbstractOAuth2Client
{
	protected $providerName = 'facebook';
    protected $imageService;
    protected $entityManager;

    public function __construct($entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    } 

    public function generateToken(Request $request) 
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
                ->setMethod(Request::METHOD_POST)
                ->setParameterPost([
                    'code'          => $request->getQuery('code'),
                    'client_id'     => $this->options->getClientId(),
                    'client_secret' => $this->options->getClientSecret(),
                    'redirect_uri'  => $this->options->getRedirectUri()
                ]);
            $responseContent = $this->httpClient->send()->getContent();
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
        $url = $this->options->getAuthUri().'?'
            . 'redirect_uri='  . urlencode($this->options->getRedirectUri())
            . '&client_id='    . $this->options->getClientId()
            . '&state='        . $this->generateState()
            . $this->getScope(',');
        return $url;
    }

    public function createUser($userProviderId, $email) 
    {
        $userEntityInfo = $this->entityManager->getClassMetadata('VisoftBaseModule\Entity\UserInterface');
        $user = new $userEntityInfo->name;
        $user->setProviderId($this->providerName, $userProviderId);
        $user->setAvatar($this->getAvatar($userProviderId));
        $user->setRole($entityManager->getRepository('VisoftBaseModule\Entity\UserRole')->findOneBy(['name' => 'member']));
        $user->setState($entityManager->getRepository('VisoftMailerModule\Entity\ContactState')->findOneBy(['name' => 'Confirmed']));
        $user->setEmail($email);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user;
        // var_dump($facebookPictureUrl);
        // die('in create user');
    }

    public function updateUser(&$user, $userProviderId)
    {
        if(empty($user->getProviderId($this->providerName))) // update provider ID
            $user->setProviderId($this->providerName, $userProviderId);
        if(empty($user->getAvatar)) // update user profile image 
            $user->setAvatar($this->getAvatar($userProviderId));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function getAvatar($userProviderId)
    {
        $profileImageUrl = "https://graph.facebook.com/" . $userProviderId . "/picture";
        $avatar = new Entity\Image();
        $avatar->setOriginalSize($profileImageUrl . "?width=1000&height=1000");
        $avatar->setXsSize($profileImageUrl . "?width=64&height=64");
        $avatar->setSSize($profileImageUrl . "?width=224&height=224");
        $avatar->setMSize($profileImageUrl . "?width=512&height=512");
        $avatar->setLSize($profileImageUrl . "?width=940&height=940");
        $this->entityManager->persist($avatar);
        return $avatar;
    }

    // public function getProfileImageUrl($userProviderId)
    // {
    //     return "https://graph.facebook.com/" . $userProviderId . "/picture";
    // }
}