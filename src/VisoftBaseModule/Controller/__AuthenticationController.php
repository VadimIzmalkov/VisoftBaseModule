<?php

namespace VisoftBaseModule\Controller;

use Zend\View\Model\ViewModel;

class AuthenticationController extends \Zend\Mvc\Controller\AbstractActionController
{
	// third part services
	protected $entityManager;
	protected $doctineAuthenticationService;

	// internals
	protected $userService;
	protected $options;

	// custom variables
    protected $templates;
    protected $layouts;
    protected $redirects;
    protected $forms;

    public function __construct($entityManager, $doctineAuthenticationService, $options, $userService) 
    {
        $this->entityManager = $entityManager;
        $this->doctineAuthenticationService = $doctineAuthenticationService;

		$this->userService = $userService;
        $this->options = $options;

        $this->templates = $options->getTemplates();
        $this->layouts = $options->getLayouts();
        $this->forms = $options->getForms();
        $this->redirects = $options->getRedirects();
    }

    public function signInAction()
    {
        if ($this->doctineAuthenticationService->hasIdentity()) {
            $route = $this->redirects['authenticated']['route'];
            return $this->redirect()->toRoute($route);
        }

        $form = new $this->forms['sign-in']($this->entityManager, 'sign-in');
        $form->setAttributes(['action' => $this->request->getRequestUri()]);
        $viewModel = new ViewModel([
            'form' => $form,
            // TODO: remove that
            'facebookSignInUrl' => $this->social()->getSignInUrl('facebook'),
            'linkedinSignInUrl' => $this->social()->getSignInUrl('linkedin'),
        ]);
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            if ($form->isValid()) {

                $data = $form->getData();
                $adapter = $this->doctineAuthenticationService->getAdapter();
                $email = $this->params()->fromPost('email');
                // $password = 
                $user = $this->entityManager->getRepository('VisoftBaseModule\Entity\UserInterface')->findOneBy(['email' => $email]);
                if(empty($user)) {
                    $this->flashMessenger()->addMessage('The username or email is not valid!');
                    return $this->redirect()->toRoute('sign-in'); 
                }
                $adapter->setIdentityValue($user->getEmail());
                $adapter->setCredentialValue($this->params()->fromPost('password'));
                $authenticationResult = $this->doctineAuthenticationService->authenticate();

                // var_dump($user->getEmail());
                // var_dump($this->params()->fromPost('password'));

                // var_dump($authenticationResult);
                // var_dump($authenticationResult->isValid());
                // $user->setPassword('123');
                // $this->entityManager->persist($user);
                // $this->entityManager->flush();

                // $passEncrypted = \VisoftBaseModule\Service\RegistrationService::encryptPassword('123');
                // var_dump($passEncrypted);
                // var_dump(\VisoftBaseModule\Service\RegistrationService::verifyHashedPasswordTest($passEncrypted, '123'));

                // $user->setPassword('123');
                // $this->entityManager->persist($user);
                // $this->entityManager->flush();
                // var_dump(\VisoftBaseModule\Service\RegistrationService::verifyHashedPassword($user, '123'));

                // // var_dump($bcrypt->verify('123', $passEncrypted));
                // // var_dump($bcrypt->verify('123', $user->getPassword()));
                // die('dddd');

                if ($authenticationResult->isValid()) {
                    $identity = $authenticationResult->getIdentity();
                    $this->doctineAuthenticationService->getStorage()->write($identity);
                    
                    // if ($this->params()->fromPost('rememberMe')) {
                    //     $time = 1209600; // 14 days (1209600/3600 = 336 hours => 336/24 = 14 days)
                    //     $sessionManager = new SessionManager();
                    //     $sessionManager->rememberMe($time);
                    // }
                    // $this->getLogger()->log(\Zend\Log\Logger::INFO, 'Signed in', ['user' => $this->identity()]);
                    if(isset($cookie->requestedUri)) {
                        $requestedUri = $cookie->requestedUri;
                        $redirectUri = $this->getRequest()->getUri()->getScheme() . '://' . $this->getRequest()->getUri()->getHost() . $requestedUri;
                        return $this->redirect()->toUrl($redirectUri);
                    }  else {
                        $route = $this->redirects['sign-in']['route'];
                        // $parameters = $this->redirects['sign-in']['parameters'];
                        return $this->redirect()->toRoute($route);
                    }
                }
            }
        }
        $viewModel->setTemplate($this->templates['sign-in']);
        $this->layout($this->layouts['sign-in']);
        return $viewModel;
    }

    public function signUpAction()
    {
        if ($this->doctineAuthenticationService->hasIdentity()) {
            $route = $this->redirects['authenticated']['route'];
            return $this->redirect()->toRoute($route);
        }
        $form = new $this->forms['sign-up']($this->entityManager, 'sign-up');
        $form->setAttributes(['action' => $this->request->getRequestUri()]);
        $viewModel = new ViewModel([
            'form' => $form,
            // TODO: remove that
            'facebookSignInUrl' => $this->social()->getSignInUrl('facebook'),
            'linkedinSignInUrl' => $this->social()->getSignInUrl('linkedin'),
        ]);
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            if($form->isValid()) {
                if($this->userService->signUp($post['email'], $post['password'], $post['fullName'])) {
                    
                    // toggle "Sign-up" activity
                    $this->userActivityLogger()->log($this->identity(), 'Signed up');

                    $this->flashMessenger()->addInfoMessage('We just sent you an email asking you to confirm your registration. Please search for fryday@fryady.net in your inbox and click on the "Confirm my registration" button');
                    $route = $this->redirects['sign-up']['route'];
                    $parameters = $this->redirects['sign-up']['parameters'];
                    return $this->redirect()->toRoute($route, $parameters);
                }
            }
        }
        $viewModel->setTemplate($this->templates['sign-up']);
        $this->layout($this->layouts['sign-up']);
        return $viewModel;
    }

	public function oAuth2Action()
	{
		$cookie = $this->request->getCookie();
		$provider = $this->params()->fromRoute('provider');
		$code =$this->params()->fromQuery('code');
		switch ($provider) {
			case 'facebook':
				$this->oAuth2Client = $this->getServiceLocator()->get('VisoftBaseModule\Service\OAuth2\FacebookClient');
				break;
			case 'linkedin':
				$this->oAuth2Client = $this->getServiceLocator()->get('VisoftBaseModule\Service\OAuth2\LinkedInClient');
				break;
			default:
				throw new \Exception("Provider not defined", 1);
				break;
		}
		// setting scope
		// $this->oAuth2Client->getOptions()->setScope(['email']); SET IN CONFIG

		if (strlen($code) > 10) {
			// send request to facebook, generate token and save it to session
			$result = $this->oAuth2Client->generateToken($this->request);

			if($result)
				// token in session
                $token = $this->oAuth2Client->getSessionToken();
            else 
            	// last returned error (array)
                $token = $this->oAuth2Client->getError(); 

            // setting OAuth2 Client
            $adapter = $this->doctineAuthenticationService->getAdapter();
            $adapter->setOAuth2Client($this->oAuth2Client);

            // authenticate
            $authenticationResult = $this->doctineAuthenticationService->authenticate();

            if (!$authenticationResult->isValid()) {
                foreach ($authenticationResult->getMessages() as $message)
                    echo "$message\n";
                echo 'no valid';
            } else {
                echo 'valid';
                $identity = $authenticationResult->getIdentity();
                $this->doctineAuthenticationService->getStorage()->write($identity);
            }

            // redirect
            if(isset($cookie->requestedUri)) {
            	// redirect to requested page
                $requestedUri = $cookie->requestedUri;
                $redirectUri = $this->getRequest()->getUri()->getScheme() . '://' . $this->getRequest()->getUri()->getHost() . $requestedUri;
                return $this->redirect()->toUrl($redirectUri);
            } else {
            	if($this->oAuth2Client->getNewUserFlag())
            		$redirectRoute = $this->redirects['sign-up']['route'];
            	else 
            		$redirectRoute = $this->redirects['sign-in']['route'];
                return $this->redirect()->toRoute($redirectRoute);
            }
		}
	}

    // public function signUpAction()
    // {
    //     if ($this->doctineAuthenticationService->hasIdentity()) {
    //         $route = $this->redirects['authenticated']['route'];
    //         return $this->redirect()->toRoute($route);
    //     }
    //     $form = new $this->forms['sign-up']($this->entityManager, 'sign-up');
    //     $form->setAttributes(['action' => $this->request->getRequestUri()]);
    //     $viewModel = new ViewModel([
    //         'form' => $form,
    //         // TODO: remove that
    //         'facebookSignInUrl' => $this->social()->getSignInUrl('facebook'),
    //         'linkedinSignInUrl' => $this->social()->getSignInUrl('linkedin'),
    //     ]);
    //     if ($this->request->isPost()) {
    //         $post = $this->request->getPost();
    //         $form->setData($post);
    //         if($form->isValid()) {
    //             if($this->userService->signUp($post['email'], $post['password'], $post['fullName'])) {
                    
    //                 // toggle "Sign-up" activity
    //                 $this->userActivityLogger()->log($this->identity(), 'Signed up');

    //                 $this->flashMessenger()->addInfoMessage('We just sent you an email asking you to confirm your registration. Please search for fryday@fryady.net in your inbox and click on the "Confirm my registration" button');
    //                 $route = $this->redirects['sign-up']['route'];
    //                 $parameters = $this->redirects['sign-up']['parameters'];
    //                 return $this->redirect()->toRoute($route, $parameters);
    //             }
    //         }
    //     }
    //     $viewModel->setTemplate($this->templates['sign-up']);
    //     $this->layout($this->layouts['sign-up']);
    //     return $viewModel;
    // }

    // public function signInAction()
    // {
    //     if ($this->doctineAuthenticationService->hasIdentity()) {
    //         $route = $this->redirects['authenticated']['route'];
    //         return $this->redirect()->toRoute($route);
    //     }

    //     $form = new $this->forms['sign-in']($this->entityManager, 'sign-in');
    //     $form->setAttributes(['action' => $this->request->getRequestUri()]);
    //     $viewModel = new ViewModel([
    //         'form' => $form,
    //         // TODO: remove that
    //         'facebookSignInUrl' => $this->social()->getSignInUrl('facebook'),
    //         'linkedinSignInUrl' => $this->social()->getSignInUrl('linkedin'),
    //     ]);
    //     if ($this->request->isPost()) {
    //         $post = $this->request->getPost();
    //         $form->setData($post);
    //         if ($form->isValid()) {

    //             $data = $form->getData();
    //             $adapter = $this->doctineAuthenticationService->getAdapter();
    //             $email = $this->params()->fromPost('email');
    //             // $password = 
    //             $user = $this->entityManager->getRepository('VisoftBaseModule\Entity\UserInterface')->findOneBy(['email' => $email]);
    //             if(empty($user)) {
    //                 $this->flashMessenger()->addMessage('The username or email is not valid!');
    //                 return $this->redirect()->toRoute('sign-in'); 
    //             }
    //             $adapter->setIdentityValue($user->getEmail());
    //             $adapter->setCredentialValue($this->params()->fromPost('password'));
    //             $authenticationResult = $this->doctineAuthenticationService->authenticate();

    //             // var_dump($user->getEmail());
    //             // var_dump($this->params()->fromPost('password'));

    //             // var_dump($authenticationResult);
    //             // var_dump($authenticationResult->isValid());
    //             // $user->setPassword('123');
    //             // $this->entityManager->persist($user);
    //             // $this->entityManager->flush();

    //             // $passEncrypted = \VisoftBaseModule\Service\RegistrationService::encryptPassword('123');
    //             // var_dump($passEncrypted);
    //             // var_dump(\VisoftBaseModule\Service\RegistrationService::verifyHashedPasswordTest($passEncrypted, '123'));

    //             // $user->setPassword('123');
    //             // $this->entityManager->persist($user);
    //             // $this->entityManager->flush();
    //             // var_dump(\VisoftBaseModule\Service\RegistrationService::verifyHashedPassword($user, '123'));

    //             // // var_dump($bcrypt->verify('123', $passEncrypted));
    //             // // var_dump($bcrypt->verify('123', $user->getPassword()));
    //             // die('dddd');

    //             if ($authenticationResult->isValid()) {
    //                 $identity = $authenticationResult->getIdentity();
    //                 $this->doctineAuthenticationService->getStorage()->write($identity);
                    
    //                 // if ($this->params()->fromPost('rememberMe')) {
    //                 //     $time = 1209600; // 14 days (1209600/3600 = 336 hours => 336/24 = 14 days)
    //                 //     $sessionManager = new SessionManager();
    //                 //     $sessionManager->rememberMe($time);
    //                 // }
    //                 // $this->getLogger()->log(\Zend\Log\Logger::INFO, 'Signed in', ['user' => $this->identity()]);
    //                 if(isset($cookie->requestedUri)) {
    //                     $requestedUri = $cookie->requestedUri;
    //                     $redirectUri = $this->getRequest()->getUri()->getScheme() . '://' . $this->getRequest()->getUri()->getHost() . $requestedUri;
    //                     return $this->redirect()->toUrl($redirectUri);
    //                 }  else {
    //                     $route = $this->redirects['sign-in']['route'];
    //                     // $parameters = $this->redirects['sign-in']['parameters'];
    //                     return $this->redirect()->toRoute($route);
    //                 }
    //             }
    //         }
    //     }
    //     $viewModel->setTemplate($this->templates['sign-in']);
    //     $this->layout($this->layouts['sign-in']);
    //     return $viewModel;
    // }
}