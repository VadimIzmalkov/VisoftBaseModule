<?php

namespace VisoftBaseModule\Service\OAuth2;

use Zend\Http\Client,
    Zend\Http\PhpEnvironment\Request,
    Zend\Session\Container;

use VisoftBaseModule\Options\OAuth2Options;

abstract class AbstractOAuth2Client 
{
    protected $entityManager;
	protected $session;
    protected $options;
    protected $error;
    protected $httpClient;
    protected $newUserFlag = false;
    protected $userService;

    abstract public function getUrl();
    abstract public function generateToken(Request $request);

    public function __construct($entityManager, $userService)
    {
        $this->entityManager = $entityManager;
        $this->userService = $userService;

        $this->session = new \Zend\Session\Container('OAuth2_' . get_class($this));
        $this->httpClient = new \Zend\Http\Client(null, [
            // 'timeout' => 30, 
            'adapter' => '\Zend\Http\Client\Adapter\Curl', 
            'curloptions' => [
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => 1,
            ],
        ]);
    }

    public function getNewUserFlag() { return $this->newUserFlag; }
    public function setNewUserFlag($flag) { $this->newUserFlag = $flag; }

    public function getInfo()
    {
        // if(is_object($this->session->info)) {
        //     var_dump($this->session);
        //     return $this->session->info;
        // } elseif(isset($this->session->token->access_token)) {
            
            // $urlProfile = $this->options->getInfoUri() . '?oauth2_access_token=' . $this->session->token->access_token;
            // $uri = 
            // var_dump($this->getInfoUri());
            // die('424234');
            $this->httpClient
                ->resetParameters(true)
                // ->setHeaders(array(
                //     'Accept-encoding' => 'utf-8',
                //     // 'x-li-format: json', 
                // ))
                ->setMethod(Request::METHOD_GET)
                ->setUri($this->getInfoUri());
            // $responseContent = $this->httpClient->send()->getContent();
            $response = $this->httpClient->send();
            $responseContent = json_decode($response->getBody());
            // var_dump($responseContent);
            // die('fddedrer');
            $infoObject = \Zend\Json\Decoder::decode($response->getBody());
            // var_dump($info);
            // die('ggg111');
            // var_dump($info);
            // $config = array(
            //     'adapter'   => 'Zend\Http\Client\Adapter\Curl',
            //     'curloptions' => array(
            //         CURLOPT_FOLLOWLOCATION => true,
            //         CURLOPT_RETURNTRANSFER => 1,
            //     ),
            // );
            // $uri = \Zend\Uri\Http::encodeQueryFragment($this->getInfoUri());
            // var_dump($uri);

            // $client = new \Zend\Http\Client($uri, $config);
            // var_dump($client->getUri());
            // $client->setMethod(\Zend\Http\Request::METHOD_GET);
            // $response = $client->send();
            // $info = \Zend\Json\Decoder::decode($response->getBody());
            // var_dump($info);

            // $curl = curl_init();
            // curl_setopt($curl, CURLOPT_URL, $this->getInfoUri());
            // curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            // $responseContent = curl_exec($curl);
            // // var_dump($responseContent);
            // curl_close($curl);

            // die('hhhh');
            // if(strlen(trim($infoObject)) > 0) {
                // var_dump($responseContent);
                
                // $infoObject = \Zend\Json\Decoder::decode($responseContent);
                // $infoObject->email = $infoObject->emailAddress;
                // unset($infoObject->emailAddress);
                // $infoObject->first_name = $infoObject->firstName;
                // unset($infoObject->firstName);
                // $infoObject->last_name = $infoObject->lastName;
                // unset($infoObject->lastName);
                // var_dump($infoObject);
                // die('fdsfsd');
                $this->session->info = $infoObject; //\Zend\Json\Decoder::decode($responseContent);
                // $this->session->info = \Zend\Json\Decoder::decode($responseContent);
                return $this->session->info;
            // } else {
            //     $this->error = array('internal-error' => 'Get info return value is empty.');
            //     return false;
            // }
        // } else {
        //     $this->error = array('internal-error' => 'Session access token not found.');
        //     return false;
        // }
    }

	public function setOptions(OAuth2Options $options) { $this->options = $options; }
    public function getOptions() { return $this->options; }

    public function getError() { return $this->error; }
    public function getSessionContainer() { return $this->session; }
    public function getSessionToken() { return $this->session->token; }
    public function getProvider() { return $this->providerName; }

    public function getState() { return $this->session->state; }
    protected function generateState() {
        $this->session->state = md5(microtime().'-'.get_class($this));
        return $this->session->state;
    }

    public function getScope($glue = ' ') {
        if(is_array($this->options->getScope()) AND count($this->options->getScope()) > 0) {
            $str = urlencode(implode($glue, array_unique($this->options->getScope())));
            return '&scope=' . $str;
        } else {
            return '';
        }
    }

    public function createUser($oAuth2ProfileInfo) 
    {
        $email = $oAuth2ProfileInfo['email'];
        $fullName = $oAuth2ProfileInfo['first_name'] . " " . $oAuth2ProfileInfo['last_name'];
        $avatar = $this->getProviderAvatar($oAuth2ProfileInfo);

        // user creates via service in order to add Notifications
        // password NULL because registration via Social Network
        $user = $this->userService->createUser($email, null, $fullName, $avatar);

        // add provider ID
        $user->setProviderId($this->providerName, $oAuth2ProfileInfo['id']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user;
    }

    public function updateUser(&$user, $oAuth2ProfileInfo)
    {
        if(empty($user->getProviderId($this->providerName))) // update provider ID
            $user->setProviderId($this->providerName, $oAuth2ProfileInfo['id']);
        if(empty($user->getImageTitle())) // update user profile image 
            $user->setImageTitle($this->getProviderAvatar($oAuth2ProfileInfo));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}