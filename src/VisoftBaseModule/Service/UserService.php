<?php

namespace VisoftBaseModule\Service;

/**
 * Class for general user purpose
 * - Registration (sign-up),
 * - Authentication (sign-in),
 * - Logout (sign-out) 
 */
class UserService implements UserServiceInterface
{
	protected $entityManager;
	protected $authenticationService;

	public function __construct($entityManager, $authenticationService)
	{
		$this->entityManager = $entityManager;
		$this->authenticationService = $authenticationService;
	}

	public function createUser($email, $password, $fullName, $avatar = null)
	{
		// check if user exists
		$user = $this->entityManager->getRepository('VisoftBaseModule\Entity\UserInterface')->findOneBy(['email' => $email]);
		if(empty($user)) {
			// create new user
            $userEntityInfo = $this->entityManager->getClassMetadata('VisoftBaseModule\Entity\UserInterface');
            $user = new $userEntityInfo->name;
            $user->setEmail($email);
		}

		// fill user info
        $user->setRole($this->entityManager->find('VisoftBaseModule\Entity\UserRole', 3));
        $user->setState($this->entityManager->getRepository('VisoftMailerModule\Entity\ContactState')->findOneBy(['name' => 'Not Confirmed']));
        $user->setFullName($fullName);
        if(!is_null($avatar))
        	$user->setImageTitle($avatar);
        // TODO: remove VisoftBaseModule\Service\RegistrationService. Move all the methods to this service
        if(!is_null($password))
        	// password can we NULL if registration via Social Network
        	// TODO: ask password after authentication
        	$user->setPassword(\VisoftBaseModule\Service\RegistrationService::encryptPassword($password));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // create notification 
        $this->notify($user);

        return $user;
	}

	public function signUp($email, $password, $fullName)
	{
		$user = $this->createUser($email, $password, $fullName);
		$adapter = $this->authenticationService->getAdapter();
		$adapter->setIdentityValue($user->getEmail());
		$adapter->setCredentialValue($password);
		$authenticationResult = $this->authenticationService->authenticate();
		if ($authenticationResult->isValid()) {
            $identity = $authenticationResult->getIdentity();
            $this->authenticationService->getStorage()->write($identity);
            // if ($this->params()->fromPost('rememberMe')) {
                $time = 1209600; // 14 days (1209600/3600 = 336 hours => 336/24 = 14 days)
                $sessionManager = new \Zend\Session\SessionManager();
                $sessionManager->rememberMe($time);
            // }
            return true;
            // redirect using cookie
            // if(isset($cookie->requestedUri)) {
            //     $requestedUri = $cookie->requestedUri;
            //     $redirectUri = $this->getRequest()->getUri()->getScheme() . '://' . $this->getRequest()->getUri()->getHost() . $requestedUri;
            //     return $this->redirect()->toUrl($redirectUri);
            // }
            // $this->getLogger()->log(\Zend\Log\Logger::INFO, 'Signed up', ['user' => $this->identity()]);
            // $this->flashMessenger()->addInfoMessage('We just sent you an email asking you to confirm your registration. Please search for fryday@fryady.net in your inbox and click on the "Confirm my registration" button');
            // $redirectRoute = $this->options->getSignUpRedirectRoute();
            // return $this->redirect()->toRoute($redirectRoute);
        }
        return false;
	}

	// override this method if needs to create notification of new user registered 
	// notification service should be implemented separately
	public function notify($user)
	{
		return true;
	}
}