<?php

namespace VisoftBaseModule\Controller;

class AuthenticationController extends \Zend\Mvc\Controller\AbstractActionController
{
	private $entityManager;
	private $userRepository;

    public function __construct($entityManager, $options, $userService) //$authenticationService, $options, $userService) 
    {
    	$this->entityManager = $entityManager;
    	$this->userRepository = $this->entityManager->getRepository('VisoftBaseModule\Entity\UserInterface');

    	$this->userService = $userService;
    }

    public function signInAction()
    {

    }

    public function signUpAction()
    {

    }

    // sign-in / sign-up via social networks
    public function oAuth2Action()
    {
    	if($this->request->isPost()) {
    		$post = $this->params()->fromPost();
    		$userId = $post['userId'];
    		$user = $this->entityManager->find('VisoftBaseModule\Entity\UserInterface', $userId);
    		$user->setPassword($post['password']);

    		$this->entityManager->persist($user);
    		$this->entityManager->flush();

        	$route = $this->redirects['sign-in']['route'];
            // $parameters = $this->redirects['sign-in']['parameters'];
            return $this->redirect()->toRoute($route);
    	}
    	$provider = $this->params()->fromRoute('provider');

    	$authorizationCode = $this->params()->fromQuery('code');
    	$state = $this->params()->fromQuery('state');

    	switch ($provider) {
            case 'facebook':
                $this->oAuth2Provider = $this->getServiceLocator()->get('VisoftBaseModule\OAuth2\FacebookProvider');
                break;
            case 'linkedin':
                // $this->oAuth2Client = $this->getServiceLocator()->get('VisoftBaseModule\Service\OAuth2\LinkedInClient');
                break;
            default:
                throw new \Exception("Provider not defined", 1);
                break;
        }

        // initializate provider with authorization code and state (state used for mitigating CSRF attack)
        $this->oAuth2Provider->setGrant($authorizationCode, $state);

        // get user's details from social network
        // $userProfileInfo = $this->oAuth2Provider->getUserProfileInfo();

        // find user by social or create new if not exists
        $user = $this->oAuth2Provider->getUser();

        // check user password
        $password = $user->getPassword();
        if(isset($password)) {
        	$route = $this->redirects['sign-in']['route'];
            // $parameters = $this->redirects['sign-in']['parameters'];
            return $this->redirect()->toRoute($route);
        } 

       	$form = new $this->forms['enter-password']($this->entityManager, 'enter-password');
       	$form->setAttributes(['action' => $this->request->getRequestUri()]);
       	$form->get('userId')->setValue($user->getId());
    	$viewModel->setTemplate($this->templates['enter-password']);
    	$viewModel = new ViewModel([
        	'form' => $form,
        ]);
        $viewModel->setTemplate($this->templates['enter-password']);
        return $viewModel;
    	
   //      // users info
   //      // $email = ;
   //      // $password = null;
   //      // $fullName = ;
   //      // $providerId = $userProfileInfo['id'];

   //      // find email in database
   //      $user = $userRepository->findOneBy(['email' => $email]);
   //      if(empty($user))
   //      	// find user by provider ID
   //      	$user = $userRepository->findOneBy([$provider . 'Id' => $providerId]);

   //      // if email not found create new account
   //      if(empty($user)) {
   //      	$user = $this->userService->createAccount($email, $password, $fullName, $this->oAuth2Provider);

   //      	// trigger event of sign-up activity
   //          $this->getEventManager()->trigger('userActivity', null, ['action' => 'sign-up', 'user' => $user, 'providerName' => $provider]);

   //          // redirect user for asking user to enter password
   //          $route = $this->redirects['sign-in']['enter-password'];
   //      } else {
   //      	// if user already registred just update info if needs and authenticate
   //      	$this->userService->updateAccount($email, $password, $fullName, $this->oAuth2Provider);

			// // trigger event of sign-in activity
   //          $this->getEventManager()->trigger('userActivity', null, ['action' => 'sign-in', 'user' => $user, 'providerName' => $provider]);

   //          // redirect user to requested URL if one stored in cookie
   //          $cookie = $this->request->getCookie();
	  //       if(isset($cookie->requestedUri)) {
	  //           $requestedUri = $cookie->requestedUri;
	  //           $redirectUri = $this->getRequest()->getUri()->getScheme() . '://' . $this->getRequest()->getUri()->getHost() . $requestedUri;
	  //           return $this->redirect()->toUrl($redirectUri);
	  //       }

	  //       // redirect to default page 
	  //       $route = $this->redirects['sign-in']['route'];
   //      }

   //      return $this->redirect()->toRoute($route);
    }

    // public function enterPasswordAction()
    // {
    // 	$
    // }
}