<?php
namespace VisoftBaseModule\Service\Authentication\Controller\Plugin;

class Authentication extends \Zend\Mvc\Controller\Plugin\AbstractPlugin
{
	protected $entityManager;
    protected $authenticationService;

	public function __construct($entityManager, $authenticationService)
	{
        $this->entityManager = $entityManager;
		$this->authenticationService = $authenticationService;
	}

	// public function signUp($userInfo)
	// {
	// 	$user = $this->createUser($userInfo);
	// 	$adapter = $this->authenticationService->getAdapter();
	// 	$adapter->setIdentityValue($user->getEmail());
	// 	$adapter->setCredentialValue($userInfo['password']);
	// 	$authenticationResult = $this->authenticationService->authenticate();
	// 	if ($authenticationResult->isValid()) {
 //            $identity = $authenticationResult->getIdentity();
 //            $this->authenticationService->getStorage()->write($identity);
 //            // if ($this->params()->fromPost('rememberMe')) {
 //                $time = 1209600; // 14 days (1209600/3600 = 336 hours => 336/24 = 14 days)
 //                $sessionManager = new \Zend\Session\SessionManager();
 //                $sessionManager->rememberMe($time);
 //            // }
 //            return true;
 //            // redirect using cookie
 //            // if(isset($cookie->requestedUri)) {
 //            //     $requestedUri = $cookie->requestedUri;
 //            //     $redirectUri = $this->getRequest()->getUri()->getScheme() . '://' . $this->getRequest()->getUri()->getHost() . $requestedUri;
 //            //     return $this->redirect()->toUrl($redirectUri);
 //            // }
 //            // $this->getLogger()->log(\Zend\Log\Logger::INFO, 'Signed up', ['user' => $this->identity()]);
 //            // $this->flashMessenger()->addInfoMessage('We just sent you an email asking you to confirm your registration. Please search for fryday@fryady.net in your inbox and click on the "Confirm my registration" button');
 //            // $redirectRoute = $this->options->getSignUpRedirectRoute();
 //            // return $this->redirect()->toRoute($redirectRoute);
 //        }
 //        return false;
	// }

    // public function createUser($userInfo)
    // {
    //     // check if user alredy has been registred as subscriber
    //     if(empty($user = $this->entityManager->getRepository('VisoftBaseModule\Entity\UserInterface')->findOneBy(['email' => $userInfo['email']]))) {
    //         // create new user
    //         $userEntityInfo = $this->entityManager->getClassMetadata('VisoftBaseModule\Entity\UserInterface');
    //         $user = new $userEntityInfo->name;
    //         $user->setEmail($userInfo['email']);
    //     }
    //     // fill user info
    //     $user->setRole($this->entityManager->find('VisoftBaseModule\Entity\UserRole', 3));
    //     $user->setState($this->entityManager->getRepository('VisoftMailerModule\Entity\ContactState')->findOneBy(['name' => 'Not Confirmed']));
    //     $user->setFullName($userInfo['fullName']);
    //     $user->setPassword(\VisoftBaseModule\Service\RegistrationService::encryptPassword($userInfo['password']));
    //     $this->entityManager->persist($user);
    //     $this->entityManager->flush();
    //     return $user;
    // }
}
