<?php

namespace VisoftBaseModule\Service\OAuth2;

use Zend\Http\Client,
    Zend\Http\PhpEnvironment\Request,
    Zend\Session\Container;

use VisoftBaseModule\Options\OAuth2Options;

abstract class AbstractOAuth2Client 
{
	protected $session;
    protected $options;
    protected $error;
    protected $httpClient;
    protected $newUserFlag = false;

    abstract public function getUrl();
    abstract public function generateToken(Request $request);

    public function __construct()
    {
        $this->session = new Container('OAuth2_' . get_class($this));
//         $config = array(
//     'adapter'   => 'Zend\Http\Client\Adapter\Curl',
//     'curloptions' => array(CURLOPT_FOLLOWLOCATION => true),
// );
// $client = new Zend\Http\Client($uri, $config);
        $this->httpClient = new \Zend\Http\Client(null, [
            // 'timeout' => 30, 
            'adapter' => '\Zend\Http\Client\Adapter\Curl', 
            'curloptions' => [
                CURLOPT_FOLLOWLOCATION => true,
            ],
        ]);
    }

    public function getNewUserFlag() { return $this->newUserFlag; }
    public function setNewUserFlag($flag) { $this->newUserFlag = $flag; }

    public function getInfo()
    {
        if(is_object($this->session->info)) {
            return $this->session->info;
        } elseif(isset($this->session->token->access_token)) {
            $urlProfile = $this->options->getInfoUri() . '?access_token=' . $this->session->token->access_token;
            // var_dump($urlProfile);
            // die('ff');
            $this->httpClient
                ->resetParameters(true)
                ->setHeaders(array('Accept-encoding' => 'utf-8'))
                ->setMethod(Request::METHOD_GET)
                ->setUri($urlProfile);
            $responseContent = $this->httpClient->send()->getContent();
            if(strlen(trim($responseContent)) > 0) {
                $this->session->info = \Zend\Json\Decoder::decode($responseContent);
                return $this->session->info;
            } else {
                $this->error = array('internal-error' => 'Get info return value is empty.');
                return false;
            }
        } else {
            $this->error = array('internal-error' => 'Session access token not found.');
            return false;
        }
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
}