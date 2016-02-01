<?php

namespace VisoftBaseModule\Adapter;

use Zend\Authentication\Result as AuthenticationResult,
    Zend\Log\Logger,
    Zend\ServiceManager\ServiceLocatorInterface,
    Zend\ServiceManager\ServiceLocatorAwareInterface;

use DoctrineModule\Authentication\Adapter\ObjectRepository as DoctrineAdapter,
    Doctrine\ORM\EntityManager;

use VisoftBaseModule\Service\OAuth2\AbstractOAuth2Client,
    VisoftBaseModule\Log\Writer\Doctrine as DoctrineWriter;

class AuthenticationAdapter extends DoctrineAdapter implements ServiceLocatorAwareInterface
{
    protected $entityManager;
	protected $oAuth2Client;
    protected $logger;

    public function authenticate()
    {
    	$entityManager = $this->options->getObjectManager();
        // $oAuth2ProfileInfo = $this->oAuth2Client->getInfo();
    	$userRepository = $entityManager->getRepository('VisoftBaseModule\Entity\UserInterface');
    	// $isOAuth2 = ;
        var_dump(is_object($this->oAuth2Client));
        var_dump(is_object($oAuth2ProfileInfo = $this->oAuth2Client->getInfo()));
        var_dump($this->oAuth2Client->getInfo());
        var_dump($this->oAuth2Client->getError());
        die('gg');
    	if(is_object($this->oAuth2Client) AND is_object($oAuth2ProfileInfo = $this->oAuth2Client->getInfo())) { // OAuth2 
            // $oAuth2ProfileInfo = $this->oAuth2Client->getInfo();
    		$oAuth2Code = AuthenticationResult::SUCCESS;
    		$oAuth2ProfileInfoArray = (array)$oAuth2ProfileInfo;
    		$oAuth2ProviderName = $this->oAuth2Client->getProvider();
            // find user by email
    		if(empty($user = $userRepository->findOneBy(['email' => $oAuth2ProfileInfoArray['email']]))) 
                // find user by provider ID
    			$user = $userRepository->findOneBy([$oAuth2ProviderName . 'Id' => $oAuth2ProfileInfoArray['id']]);
            // if email not registered create new account
    		if(empty($user)) {
    			$user = $this->oAuth2Client->createUser($oAuth2ProfileInfoArray, $oAuth2ProfileInfoArray['email']);
                $this->oAuth2Client->setNewUserFlag(true);
    			$this->getLogger()->log(\Zend\Log\Logger::INFO, 'Signed up via facebook', ['user' => $user]);
    		} else {
                // update remote ID and avatar (if needs)  
    			$this->oAuth2Client->updateUser($user, $oAuth2ProfileInfoArray['id']);
                $this->oAuth2Client->setNewUserFlag(false);
    			$this->getLogger()->log(\Zend\Log\Logger::INFO, 'Signed in via facebook', ['user' => $user]);
    		}
    		return new AuthenticationResult($oAuth2Code, $user);
    	} else { // not OAuth2
    		$this->setup();
            $identity = $userRepository->findOneBy([$this->options->getIdentityProperty() => $this->identity]);
            if (!$identity) {
                $this->authenticationResultInfo['code'] = AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND;
                $this->authenticationResultInfo['messages'][] = 'A record with the supplied identity could not be found.';
                return $this->createAuthenticationResult();
            }
            $authResult = $this->validateIdentity($identity);
            return $authResult;
        }
    }

    public function setOAuth2Client($oAuth2Client)
    {
        if($oAuth2Client instanceof AbstractOAuth2Client) {
            $this->oAuth2Client = $oAuth2Client;
        } else {
            throw new Exception("Client should extends AbstractOAuth2Client", 1);
        }
    }

    public function getLogger()
    {
        if(is_null($this->logger)) {
            $this->setLogger();
        }
        return $this->logger;
    }

    public function setLogger($logger = null)
    {
        if(is_null($logger)) {
            $this->logger = new Logger;
            $writer = new DoctrineWriter($this->getEntityManager());
            $this->logger->addWriter($writer);
        } else {
            $this->logger = $logger;
        }    
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    public function getServiceLocator()
    {
        return $this->services;
    }

    protected function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    protected function getEntityManager()
    {
        if (null === $this->entityManager) {
            $this->setEntityManager($this->getServiceLocator()->get('Doctrine\ORM\EntityManager'));
        }
        return $this->entityManager;
    }
}