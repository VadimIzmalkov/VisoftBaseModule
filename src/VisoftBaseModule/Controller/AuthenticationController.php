<?php

namespace VisoftBaseModule\Controller;

use Zend\View\Model\ViewModel;

class AuthenticationController extends \Zend\Mvc\Controller\AbstractActionController
{
	// third part services
	protected $entityManager;
	protected $doctineAuthenticationService;
    protected $formElementManager;

	// internals
	protected $userService;
	protected $options;
	protected $oAuth2Client;

	// custom variables
    protected $templates;
    protected $layouts;
    protected $redirects;
    protected $forms;

    public function __construct($entityManager, $doctineAuthenticationService, $options, $userService, $oAuth2Client, $formElementManager) 
    {
        $this->entityManager = $entityManager;
        $this->doctineAuthenticationService = $doctineAuthenticationService;
        $this->formElementManager = $formElementManager;

		$this->userService = $userService;
		$this->oAuth2Client = $oAuth2Client;
        $this->options = $options;

        $this->templates = $options->getTemplates();
        $this->layouts = $options->getLayouts();
        $this->forms = $options->getForms();
        $this->redirects = $options->getRedirects();
    }

    // Action just send emails with email confirmation
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
            // 'facebookSignInUrl' => $this->social()->getSignInUrl('facebook'),
            // 'linkedinSignInUrl' => $this->social()->getSignInUrl('linkedin'),
        ]);
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            if($form->isValid()) {
            	$email = $post['email'];
            	$password = $post['password'];
            	$fullName = $post['fullName'];
            	
                // find user in database or create new
            	$user = $this->userService->createUser($email, $password, $fullName);

                // send confirmation email
                $emailTemplate = 'email-templates/email-confirmation';
                $parametersArray = [
                    'confirmUrl' => $this->url()->fromRoute('fryday/account/default', [
                        'controller' => 'account',
                        'action' => 'email-confirmed'
                    ], ['query' => ['registration-token' => $user->getRegistrationToken()]]),
                ];
                $subject = 'Confirm your registration';
                // contact for confirmation email
                $contact = [
                    'email' => $email,
                    'fullName' => $fullName,
                    'registrationToken' => $user->getRegistrationToken(),
                ];
                $contactsArray = [$contact];
                $status = $this->mailerPlugin()->send($contactsArray, $emailTemplate, $parametersArray, $subject, 'email-confirmation', 'individual');
                
                // show message 
            	$this->flashMessenger()->addInfoMessage('We just sent you an email asking you to confirm your registration. Please search for fryday@fryady.net in your inbox and click on the "Confirm my registration" button');

                // trigger sign up activity
                $this->getEventManager()->trigger('signUp', null, array('provider' => 'email', 'identity' => $user));

            	$route = $this->redirects['after-sign-up-email']['route'];
            	$parameters = $this->redirects['after-sign-up-email']['parameters'];
                return $this->redirect()->toRoute($route, $parameters);
            }
        }
        $viewModel->setTemplate($this->templates['sign-up']);
        $this->layout($this->layouts['sign-up']);
        return $viewModel;
    }

    public function signInAction()
    {
        if ($this->doctineAuthenticationService->hasIdentity()) {
            $route = $this->redirects['authenticated']['route'];
            return $this->redirect()->toRoute($route);
        }

        $form = $this->formElementManager->get('UserForm', ['name' => 'Fryday Form', 'options' => ['type' => $this->forms['sign-in']]]);
        $form->setAttributes(['action' => $this->request->getRequestUri()]);
        $viewModel = new ViewModel([
            'form' => $form,
        ]);
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            if ($form->isValid()) {

                $data = $form->getData();
                $adapter = $this->doctineAuthenticationService->getAdapter();
                $email = $this->params()->fromPost('email');
                $user = $this->entityManager->getRepository('VisoftBaseModule\Entity\UserInterface')->findOneBy(['email' => $email]);
                if(empty($user)) {
                    $this->flashMessenger()->addMessage('The username or email is not valid!');
                    $route = $this->redirects['authentication-faild']['route'];
                    $parameters = isset($this->redirects['authentication-faild']['parameters']) ? $this->redirects['authentication-faild']['parameters'] : [];
                    $query = $this->redirects['authentication-faild']['query'];
                    return $this->redirect()->toRoute($route, $parameters, ['query' => $query]);
                }
                $adapter->setIdentityValue($user->getEmail());
                $adapter->setCredentialValue($this->params()->fromPost('password'));
                $authenticationResult = $this->doctineAuthenticationService->authenticate();

                if ($authenticationResult->isValid()) {
                    $identity = $authenticationResult->getIdentity();
                    $this->doctineAuthenticationService->getStorage()->write($identity);
                    
                    // if ($this->params()->fromPost('rememberMe')) {
                    //     $time = 1209600; // 14 days (1209600/3600 = 336 hours => 336/24 = 14 days)
                    //     $sessionManager = new SessionManager();
                    //     $sessionManager->rememberMe($time);
                    // }
                    // $this->getLogger()->log(\Zend\Log\Logger::INFO, 'Signed in', ['user' => $this->identity()]);

                    // trigger sign up activity
                    $this->getEventManager()->trigger('signIn', null, array('provider' => 'email', 'identity' => $identity));

                    $cookie = $this->request->getCookie();
                    if(isset($cookie->requestedUri)) {
                        // get URL for redirect to after "Sign Up"
                        $requestedUri = $cookie->requestedUri;
                        $redirectUri = $this->getRequest()->getUri()->getScheme() . '://' . $this->getRequest()->getUri()->getHost() . $requestedUri;

                        // delete cookie
                        $newCookie = new \Zend\Http\Header\SetCookie('requestedUri', $requestedUri, time(), '/');
                        $this->getResponse()->getHeaders()->addHeader($newCookie);

                        return $this->redirect()->toUrl($redirectUri);
                    }  else {
                        $route = $this->redirects['after-sign-in']['route'];
                        $parameters = isset($this->redirects['after-sign-in']['parameters']) ? $this->redirects['after-sign-in']['parameters'] : [];
                        $query = $this->redirects['after-sign-in']['query'];
                        return $this->redirect()->toRoute($route, $parameters, ['query' => $query]);
                    }
                }
            }
        }
        $viewModel->setTemplate($this->templates['sign-in']);
        $this->layout($this->layouts['sign-in']);
        return $viewModel;
    }

    public function signOutAction()
    {
        // $auth = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');
        if ($this->doctineAuthenticationService->hasIdentity()) {
            $this->doctineAuthenticationService->clearIdentity();
            $sessionManager = new \Zend\Session\SessionManager();
            $sessionManager->forgetMe();
        }
        return $this->redirect()->toRoute($this->redirects['after-sign-out']['route']);
        // return $this->redirectToRefer();
    }

    public function forgotPasswordAction()
    {
        $form = new $this->forms['forgot-password']($this->entityManager, 'forgot-password');
        $form->setAttributes(['action' => $this->request->getRequestUri()]);
        $viewModel = new ViewModel([
            'form' => $form,
        ]);
        $this->layout($this->layouts['forgot-password']);
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            if($form->isValid()) {
                $viewModel->setTemplate($this->templates['forgot-password-sent']);
                return $viewModel;
            }
        }
        $viewModel->setTemplate($this->templates['forgot-password']);
        return $viewModel;
    }

	public function oAuth2Action()
	{
		$provider = $this->params()->fromRoute('provider');
		$authorizationCode = $this->params()->fromQuery('code');
		$state = $this->params()->fromQuery('state');

		if(strlen($authorizationCode) > 10) 
        {
			// setting up OAuth2 client
			$this->oAuth2Client->setProvider($provider);
            
            // for LinkedIn redirect uri should be the same as when generating Authentication Url (with all parameters, not as in config file)
            $serverUrl = $this->getRequest()->getUri()->getScheme() . '://' . $this->getRequest()->getUri()->getHost();
            $redirectUrl = $serverUrl . parse_url($this->request->getRequestUri(), PHP_URL_PATH);
			$this->oAuth2Client->setGrant($authorizationCode, $state, $redirectUrl);

			// injecting OAuth2 client into Doctine authentication adapter
			$adapter = $this->doctineAuthenticationService->getAdapter();
			$adapter->setOAuth2Client($this->oAuth2Client);

			// authenticate
            $authenticationResult = $this->doctineAuthenticationService->authenticate();

            if($authenticationResult->isValid()) 
            {
            	$identity = $authenticationResult->getIdentity();
            	$this->doctineAuthenticationService->getStorage()->write($identity);

            	$cookie = $this->request->getCookie();
            	
                // refer-code for redirect
                $referCode = $this->params()->fromRoute('refer-code');

                $queryRedirect['refer-code'] = $referCode;
                // this parameter tells that user authenticate via O2Auth 
                $queryRedirect['action'] = 'o2auth';
	            
            	if($this->oAuth2Client->isNewUser()) 
                {
                    // trigger sign up activity
                    // $this->getEventManager()->trigger('signUp', null, array('provider' => $provider));
                    $this->getEventManager()->trigger(\Fryday\Event\Listener\ActivityListener::EVENT_ACTIVITY, null, [
                        'type'              => \Fryday\Entity\Activity::TYPE_SIGN_UP_SOCIAL,
                        'socialProvider'    => $provider,
                        'created-by'        => $identity,
                        'notify'            => [
                            'users-group'   => \Fryday\Service\GearmanJob\ActivityNotificationJobService::USERS_GROUP_ADMINISTRATORS,
                            'by-email'      => false,
                        ],
                    ]);

            		$route = $this->redirects['after-sign-up-social']['route'];
                    return $this->redirect()->toRoute($route, [], ['query' => $queryRedirect]);
                } 
                else 
                {
                    // trigger sign in activity
                    // $this->getEventManager()->trigger('signIn', null, array('provider' => $provider));
                    $this->getEventManager()->trigger(\Fryday\Event\Listener\ActivityListener::EVENT_ACTIVITY, null, [
                        'type'              => \Fryday\Entity\Activity::TYPE_SIGN_IN_SOCIAL,
                        'socialProvider'    => $provider,
                        'created-by'        => $identity,
                        'notify'            => [
                            'users-group'   => \Fryday\Service\GearmanJob\ActivityNotificationJobService::USERS_GROUP_ADMINISTRATORS,
                            'by-email'      => false,
                        ],
                    ]);

                    if(isset($cookie->requestedUri)) 
                    {
                        // redirect to requested page
                        $requestedUri = $cookie->requestedUri;
                        $redirectUri = $this->getRequest()->getUri()->getScheme() . '://' . $this->getRequest()->getUri()->getHost() . $requestedUri;

                        // delete cookie
                        $newCookie = new \Zend\Http\Header\SetCookie('requestedUri', $requestedUri, time(), '/');
                        $this->getResponse()->getHeaders()->addHeader($newCookie);

                        return $this->redirect()->toUrl($redirectUri);
                    } 
                    else 
                    {
                        $route = $this->redirects['after-sign-in']['route'];
                        return $this->redirect()->toRoute($route, [], ['query' => $queryRedirect]);
                    }  
	            }
            } 
            else 
            {
            	// if($authenticationResult)
				exit('VisoftBaseModule.Invalid.OAuth2.AuthenticationResult');
            }
		} 
        else 
        {
			// TODO: handle this error in correct way
			exit('VisoftBaseModule.Invalid.OAuth2.Code');
		}
	}

    protected function redirectToRefer()
    {
        $scheme = $this->request->getHeader('Referer')->uri()->getScheme();
        $host = $this->request->getHeader('Referer')->uri()->getHost();
        $path = $this->request->getHeader('Referer')->uri()->getPath();
        $port = $this->request->getHeader('Referer')->uri()->getPort();
        $port = is_null($port) ? null : ':' . $port;
        $query = $this->request->getHeader('Referer')->uri()->getQuery();
        $redirectUrl = $scheme . '://' . $host  . $port . $path . '?' . $query;
        return $this->redirect()->toUrl($redirectUrl);
    }
}