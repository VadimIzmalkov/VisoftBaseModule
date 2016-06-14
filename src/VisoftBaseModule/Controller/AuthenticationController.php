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
	protected $oAuth2Client;

	// custom variables
    protected $templates;
    protected $layouts;
    protected $redirects;
    protected $forms;

    public function __construct($entityManager, $doctineAuthenticationService, $options, $userService, $oAuth2Client) 
    {
        $this->entityManager = $entityManager;
        $this->doctineAuthenticationService = $doctineAuthenticationService;

		$this->userService = $userService;
		$this->oAuth2Client = $oAuth2Client;
        $this->options = $options;

        $this->templates = $options->getTemplates();
        $this->layouts = $options->getLayouts();
        $this->forms = $options->getForms();
        $this->redirects = $options->getRedirects();
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
                $status = $this->mailerPlugin()->send($contactsArray, $emailTemplate, $parametersArray, $subject, 'email-confirmation','bulk');
                
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

	public function oAuth2Action()
	{
		$provider = $this->params()->fromRoute('provider');
		$authorizationCode = $this->params()->fromQuery('code');
		$state = $this->params()->fromQuery('state');

		if(strlen($authorizationCode) > 10) {
			// setting up OAuth2 client
			$this->oAuth2Client->setProvider($provider);
			$this->oAuth2Client->setGrant($authorizationCode, $state);

			// injecting OAuth2 client into Doctine authentication adapter
			$adapter = $this->doctineAuthenticationService->getAdapter();
			$adapter->setOAuth2Client($this->oAuth2Client);

			// authenticate
            $authenticationResult = $this->doctineAuthenticationService->authenticate();

            if($authenticationResult->isValid()) {
            	$identity = $authenticationResult->getIdentity();
            	$this->doctineAuthenticationService->getStorage()->write($identity);

            	$cookie = $this->request->getCookie();
            	// redirect
	            if(isset($cookie->requestedUri)) {
                    // trigger sign in activity
                    $this->getEventManager()->trigger('signIn', null, array('provider' => $provider));
	            	
                    // redirect to requested page
	                $requestedUri = $cookie->requestedUri;
	                $redirectUri = $this->getRequest()->getUri()->getScheme() . '://' . $this->getRequest()->getUri()->getHost() . $requestedUri;
	                return $this->redirect()->toUrl($redirectUri);
	            } else {
	            	if($this->oAuth2Client->isNewUser()) {
                        $this->getEventManager()->trigger('signUp', null, array('provider' => $provider));
	            		$redirectRoute = $this->redirects['after-sign-up-social']['route'];
                    }
	            	else {
                        // trigger sign in activity
                        $this->getEventManager()->trigger('signIn', null, array('provider' => $provider));
	            		$redirectRoute = $this->redirects['sign-in']['route'];
                    }
	                return $this->redirect()->toRoute($redirectRoute);
	            }
            } else {
            	// TODO: handle this error in correct way
				exit('VisoftBaseModule.Invalid.OAuth2.AuthenticationResult');
            }
		} else {
			// TODO: handle this error in correct way
			exit('VisoftBaseModule.Invalid.OAuth2.Code');
		}
	}
}