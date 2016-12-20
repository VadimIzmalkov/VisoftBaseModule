<?php 

namespace VisoftBaseModule\OAuth2;

class OAuth2Client implements \Zend\ServiceManager\ServiceLocatorAwareInterface
{
	protected $serviceLocator;
	private $userService;
    private $entityManager;
	private $oAuth2Provider = null;
    protected $isNewUser = false;

    private $logger;

	public function __construct($entityManager, $userService)
	{
        $this->entityManager = $entityManager;
		$this->userService = $userService;

        // init logger
        $logFileDir = getcwd() . '/data/VisoftBaseModule/log/';
        $logFilePath = $logFileDir . 'oauth2-client.log';
        \VisoftBaseModule\Controller\Plugin\AccessoryPlugin::checkDir($logFileDir);
        $this->logger = new \Zend\Log\Logger;
        $writer = new \Zend\Log\Writer\Stream($logFilePath);
        $this->logger->addWriter($writer);
	}

	public function setProvider($providerName)
	{
		switch ($providerName) {
			case 'facebook':
				$this->oAuth2Provider = $this->serviceLocator->get('VisoftBaseModule\OAuth2\FacebookProvider');
				break;
			case 'linkedin':
				$this->oAuth2Provider = $this->serviceLocator->get('VisoftBaseModule\OAuth2\LinkedinProvider');
				break;
			default:
				throw new \Exception("Provider not defined", 1);
				break;
		}
	}

    public function setGrant($authorizationCode, $providerState, $redirectUri = null)
    {
        if(is_null($this->oAuth2Provider)) {
            // TODO: fix handling this error
            exit('VisoftBaseModule.Provaider.Not.Initialised');
        }
        $this->oAuth2Provider->authorizationCode = $authorizationCode;
        $this->oAuth2Provider->providerState = $providerState;
        $this->oAuth2Provider->redirectUri = $redirectUri;

        $this->logger->info('Set grant: (c): ' . $authorizationCode . ', (s): ' . $providerState);
    }

	// find user in database and update, or if not exists - create
	public function getIdentity()
	{
		// if authentication faild should return null (VisoftBaseModule\Adapter\AuthenticationAdapter)
        
        // get user's details from social network
        $userProfileInfo = $this->oAuth2Provider->getUserProfileInfo();

        // user profile info
        $email = $userProfileInfo['email'];
        $fullName = $userProfileInfo['fullName'];
        $providerId = $userProfileInfo['providerId'];

        // log recieved data
        $this->logger->info('Get profile: (e): ' . $email . ', (fN): ' . $fullName . ', (pId): ' . $providerId);

        // check if user exists
        $identity = $this->entityManager->getRepository('VisoftBaseModule\Entity\UserInterface')->findOneBy(['email' => $email]);
        if(empty($identity)) {
            $identity = $this->userService->createOAuth2User($email);
            $this->isNewUser = true;

        // TODO: fix this dependency (depend on role)
        } elseif($identity->getRole()->getName() === 'subscriber') {
            $this->isNewUser = true;
            $identity->setRegistrationDate(new \DateTime());
            $identity->setRole($this->entityManager->find('VisoftBaseModule\Entity\UserRole', 3));
        }

        // solution for fix
        // $this->isNewUser = $this->userService->createOAuth2User($email); // true - new user, false - old user
        // $identity = $this->entityManager->getRepository('VisoftBaseModule\Entity\UserInterface')->findOneBy(['email' => $email]);


        // set user as active
        if(!$identity->getActive())
            $identity->setActive(true);
        
        if(empty($identity->getProviderId($this->oAuth2Provider->getProviderName())))
            $identity->setProviderId($this->oAuth2Provider->getProviderName(), $providerId);

        if(empty($identity->getFullName()))
            $identity->setFullName($fullName);

        if(empty($identity->getImageTitle())) {
            $socialAvatar = $this->oAuth2Provider->getAvatar($providerId);
            $identity->setImageTitle($socialAvatar);
        }

        $this->entityManager->persist($identity);
        $this->entityManager->flush();

        return $identity;
	}

    public function isNewUser()
    {
        return $this->isNewUser;
    }

	public function setServiceLocator(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator) {
    	$this->serviceLocator = $serviceLocator;
  	}

  	public function getServiceLocator() {
    	return $this->serviceLocator;
  	}
}