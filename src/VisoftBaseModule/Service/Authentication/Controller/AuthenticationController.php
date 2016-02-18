<?php

namespace VisoftBaseModule\Service\Authentication\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\SessionManager;
use Zend\Session\Config\StandardConfig;

// use Base\Options\ModuleOptions,
//     Base\Service\UserService as UserCredentialsService,
//     Base\Form;

/**
 * Index controller
 */
class AuthenticationController extends AbstractActionController
{
    protected $authenticationService;

    protected $entityManager;

    protected $options;
    protected $templates;
    protected $layouts;
    protected $redirects;

    public function __construct($entityManager, $authenticationService, $options) 
    {
        $this->moduleOptions = $options;
        $this->templates = $options->getTemplates();
        $this->layouts = $options->getLayouts();
        $this->forms = $options->getForms();
        $this->redirects = $options->getRedirects();
        $this->entityManager = $entityManager;
        $this->authenticationService = $authenticationService;
    }

    public function signInAction()
    {
        // var_dump($this->redirects['sign-in']);
            // die();
        if ($this->authenticationService->hasIdentity()) {
            // var_dump($this->options->getLoginRedirectRoute());
            // die();
            // var_dump($this->redirects['has-identity']);
            // die('ddd');
            $route = $this->redirects['sign-in']['route'];
            // $parameters = $this->redirects['sign-in']['parameters'];
            // var_dump($this->redirects);
            // var_dump($route);
            // var_dump($parameters);
            // die('ddd');
            return $this->redirect()->toRoute($route);
        }
        $form = new $this->forms['sign-in']($this->entityManager, 'sign-in');
        $form->setAttributes(['action' => $this->request->getRequestUri()]);
        $viewModel = new ViewModel([
            'form' => $form,
        ]);
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            if ($form->isValid()) {
                $data = $form->getData();
                $adapter = $this->authenticationService->getAdapter();
                $email = $this->params()->fromPost('email');
                $password = 
                $user = $this->entityManager->getRepository('VisoftBaseModule\Entity\UserInterface')->findOneBy(['email' => $email]);
                if(empty($user)) {
                    $this->flashMessenger()->addMessage('The username or email is not valid!');
                    return $this->redirect()->toRoute('sign-in'); 
                }
                $adapter->setIdentityValue($user->getEmail());
                $adapter->setCredentialValue($this->params()->fromPost('password'));
                $authenticationResult = $this->authenticationService->authenticate();

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
                    $this->authenticationService->getStorage()->write($identity);
                    
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
        // $authenticationService = $serviceManader->get('Zend\Authentication\AuthenticationService');
        // if ($user = $this->identity()) {
        //     // var_dump($this->options->getLoginRedirectRoute());
        //     // die();
        //     return $this->redirect()->toRoute($this->options->getLoginRedirectRoute());
        // }

        // $request = $this->getRequest();
        // $cookie = $this->getRequest()->getCookie();
        // $form = $this->getServiceLocator()->get('FormElementManager')->get('SignInForm');
        // $newPasswordRequestForm = $this->getServiceLocator()->get('FormElementManager')->get('PasswordResetRequestForm');
        // $messages = null;
        // if ($request->isPost()) {
        //     // $form->setValidationGroup('usernameOrEmail', 'password', 'rememberme', 'csrf', 'captcha');
        //     $post = $request->getPost();
        //     $form->setData($post);
        //     if ($form->isValid()) {
        //         $data = $form->getData();
        //         // $authService = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');
        //         $adapter = $this->authService->getAdapter();
        //         $email = $this->params()->fromPost('email');
        //         // var_dump($email);
        //         // try {
        //             $user = $this->entityManager->getRepository($this->options->getUserClass())->findOneBy(['email' => $email]);
        //             if(empty($user)) {
        //                 $this->flashMessenger()->addMessage('The username or email is not valid!');
        //                 return $this->redirect()->toRoute('accounts/login'); 
        //             }
                    
        //             // if($user->getState()->getId() < 2) {
        //             //     $this->flashMessenger()->addMessage('Your username is disabled. Please contact an administrator.');
        //             //     return $this->redirect()->toRoute('accounts/login'); 
        //             // }

        //             $adapter->setIdentityValue($user->getEmail());
        //             $adapter->setCredentialValue($this->params()->fromPost('password'));

        //             $authResult = $this->authService->authenticate();
        //             if ($authResult->isValid()) {
        //                 $identity = $authResult->getIdentity();
        //                 $this->authService->getStorage()->write($identity);
                        
        //                 if ($this->params()->fromPost('rememberMe')) {
        //                     $time = 1209600; // 14 days (1209600/3600 = 336 hours => 336/24 = 14 days)
        //                     $sessionManager = new SessionManager();
        //                     $sessionManager->rememberMe($time);
        //                 }
        //                 $this->getLogger()->log(\Zend\Log\Logger::INFO, 'Signed in', ['user' => $this->identity()]);
        //                 if(isset($cookie->requestedUri)) {
        //                     $requestedUri = $cookie->requestedUri;
        //                     $redirectUri = $this->getRequest()->getUri()->getScheme() . '://' . $this->getRequest()->getUri()->getHost() . $requestedUri;
        //                     return $this->redirect()->toUrl($redirectUri);
        //                 }  else 
        //                     return $this->redirect()->toRoute($this->options->getLoginRedirectRoute());
        //             }
                    
        //             foreach ($authResult->getMessages() as $message) {
        //                 $this->flashMessenger()->addErrorMessage($message);
        //                 // die();
        //         //       $messages .= "$message\n";
        //             }

        //             return $this->redirect()->toRoute('accounts/login');
        //         // } catch (\Exception $e) {
        //         //     return $this->getServiceLocator()->get('csnuser_error_view')->createErrorView(
        //         //         $this->getTranslatorHelper()->translate('Something went wrong during login! Please, try again later.'),
        //         //         $e,
        //         //         $this->getOptions()->getDisplayExceptions(),
        //         //         $this->getOptions()->getNavMenu()
        //         //     );
        //         // }
        //     }
        // }
        // // generate url "Connect with Facebook"
        // $facebookClient = $this->getServiceLocator()->get('VisoftBaseModule\Service\OAuth2\FacebookClient');
        // // $facebookClient->getOptions()->setScope(['email']);
        // $facebookSignInUrl = $facebookClient->getUrl();

        // $viewModel = new ViewModel([
        //     // 'flashMessages' => $this->flashMessenger()->getMessages(),
        //     'form' => $form,
        //     'newPasswordRequestForm' => $newPasswordRequestForm,
        //     'facebookSignInUrl' => $facebookSignInUrl,
        // ]);
        // $viewModel->setTemplate($this->options->getLoginTemplate());
        // $this->layout('layout/layout-empty.phtml');
        // return $viewModel;
    }

    public function signOutAction()
    {
        // $auth = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');
        if ($this->authenticationService->hasIdentity()) {
            $this->authenticationService->clearIdentity();
            $sessionManager = new SessionManager();
            $sessionManager->forgetMe();
        }
        return $this->redirect()->toRoute($this->redirects['sign-out']['route']);
    }
    /**
     * @var ModuleOptions
     */
    // protected $options;
    /**
     * @var Doctrine\ORM\EntityManager
     */
    // protected $entityManager;
    // protected $authService;
    // protected $mailService;
    /**
     * @var Zend\Form\Form
     */
    // protected $userFormHelper;

    // public function __construct(
    // 	$entityManager,
    // 	$options,
    //     $authService,
    //     $mailService
    // )
    // {
    // 	$this->entityManager = $entityManager;
    // 	$this->options = $options;
    //     $this->authService = $authService;
    //     $this->mailService = $mailService;
    // }

    // public function signUpAction()
    // {
    //     if ($user = $this->identity()) {
    //         return $this->redirect()->toRoute($this->options->getLoginRedirectRoute());
    //     }
    //     // $me = $this->getServiceLocator()->get('ReverseOAuth2\Facebook');
    //     // $me->getOptions()->setScope(['email']);
    //     $request = $this->getRequest();
    //     $userClass = $this->options->getUserClass();
    //     if(!class_exists($userClass))
    //         // TODO: fix exception
    //         throw new Exception("Error Processing Request", 1);
    //     $user = new $userClass;
    //     // $form = $this->getServiceLocator()->get('FormElementManager')->get('SignUpForm');
    //     $form = new Form\SignUpForm($this->entityManager);
    //     $form->setAttributes(['action' => $this->getRequest()->getRequestUri()]);
    //     // var_dump($form->getInputFilter()->getInputs());
    //     $filter = new Form\SignUpFilter($this->entityManager);
    //     $form->setInputFilter($filter);
    //     $form->bind($user);
    //     $url = null;
    //     if($request->isPost()) {
    //         $post = $request->getPost();
    //         $email = $this->params()->fromPost('email');
    //         $form->setData($post);
    //         if($form->isValid()) {
    //             /* create new user */
    //             // if validation is ok, then user can already be created as subscriber
    //             if($userExist = $this->entityManager->getRepository($this->options->getUserClass())->findOneBy(['email' => $email]))
    //                 $user = $userExist;
    //             $user->setRole($this->entityManager->find('VisoftBaseModule\Entity\UserRole', 3));
    //             $user->setState($this->entityManager->getRepository('VisoftMailerModule\Entity\ContactState')->findOneBy(['name' => 'Not Confirmed']));
    //             $user->setRegistrationToken(md5(uniqid(mt_rand(), true)));
    //             $user->setPassword(UserCredentialsService::encryptPassword($post['password']));
    //             $user->setFullName($post['fullName']);
    //             $this->entityManager->persist($user);
    //             $this->entityManager->flush();
                
    //             /* send confirmation email */
    //             $this->mailService->setSubject('Confirm your registration');
    //             $confirmUrl = $this->getRequest()->getUri()->getScheme() . '://' // http://
    //                 . $this->getRequest()->getUri()->getHost() // fryday.net or localhost domain
    //                 . $this->url()->fromRoute('accounts/confirm-email', ['token' => $user->getRegistrationToken()]); // rest
    //             // TODO: template name should be an option 
    //             $this->mailService->setTemplate('mailer/templates/email-confirm', [
    //                 'confirmUrl' => $confirmUrl,
    //             ]);
    //             $message = $this->mailService->getMessage();
    //             $message->setTo($user->getEmail());
    //             $result = $this->mailService->send();

    //             /* authenticate new user */
    //             $adapter = $this->authService->getAdapter();
    //             $user = $this->entityManager->getRepository($this->options->getUserClass())->findOneBy(['email' => $email]);
    //             $adapter->setIdentityValue($user->getEmail());
    //             $adapter->setCredentialValue($this->params()->fromPost('password'));
    //             $authResult = $this->authService->authenticate();
    //             if ($authResult->isValid()) {
    //                 $identity = $authResult->getIdentity();
    //                 $this->authService->getStorage()->write($identity);
    //                 if ($this->params()->fromPost('rememberMe')) {
    //                     $time = 1209600; // 14 days (1209600/3600 = 336 hours => 336/24 = 14 days)
    //                     $sessionManager = new SessionManager();
    //                     $sessionManager->rememberMe($time);
    //                 }
    //                 // redirect using cookie
    //                 // if(isset($cookie->requestedUri)) {
    //                 //     $requestedUri = $cookie->requestedUri;
    //                 //     $redirectUri = $this->getRequest()->getUri()->getScheme() . '://' . $this->getRequest()->getUri()->getHost() . $requestedUri;
    //                 //     return $this->redirect()->toUrl($redirectUri);
    //                 // }
    //                 $this->getLogger()->log(\Zend\Log\Logger::INFO, 'Signed up', ['user' => $this->identity()]);
    //                 $this->flashMessenger()->addInfoMessage('We just sent you an email asking you to confirm your registration. Please search for fryday@fryady.net in your inbox and click on the "Confirm my registration" button');
    //                 $redirectRoute = $this->options->getSignUpRedirectRoute();
    //                 return $this->redirect()->toRoute($redirectRoute);
    //             }
    //         }
    //     }
    //     $facebookClient = $this->getServiceLocator()->get('VisoftBaseModule\Service\OAuth2\FacebookClient');
    //     $facebookSignUpUrl = $facebookClient->getUrl();
    //     $viewModel = new ViewModel([
    //         'form' => $form,
    //         'facebookSignupUrl' => $facebookSignUpUrl,
    //     ]);
    //     $viewModel->setTemplate($this->options->getSignUpTemplate());
    //     $this->layout('layout/layout-empty.phtml');
    // 	return $viewModel;
    // }

    // public function passwordResetRequestAction()
    // {
    //     $form = $this->getServiceLocator()->get('FormElementManager')->get('PasswordResetRequestForm');
    //     $viewModel = new ViewModel([
    //         'form' => $form,
    //     ]);
    //     $request = $this->getRequest();
    //     if($request->isPost()) {
    //         $post = $request->getPost();
    //         $form->setData($post);
    //         if($form->isValid()) {
    //             $email = $this->params()->fromPost('email');
    //             $user = $this->entityManager->getRepository($this->options->getUserClass())->findOneBy(['email' => $email]);
    //             $server = 'http://' . $this->getRequest()->getServer()->get('HTTP_HOST');
    //             $resetPasswordUrl = $server 
    //                 . $this->url()->fromRoute('accounts/password-reset-request', array())
    //                 . $user->getRegistrationToken();
    //             $this->mailService->setTemplate(
    //                 'base/emails-templates/link-to-change-password',
    //                 ['resetPasswordLink' => $resetPasswordUrl]
    //             );
    //             $message = $this->mailService->getMessage();
    //             $message->setSubject('This is the subject')
    //                     ->addTo($email);
    //             $result = $this->mailService->send();
    //             $viewModel->setVariables([
    //                 'email' => $email,
    //             ]);
    //             return $viewModel->setTemplate($this->options->getPasswordResetMailSentTemplate());
    //         }
    //     }
    //     return $viewModel->setTemplate($this->options->getPasswordResetRequestTemplate());
    // }

    // public function passwordResetAction()
    // {
    //     $token = $this->params()->fromRoute('token');
    //     $form = $this->getServiceLocator()->get('FormElementManager')->get('PasswordResetForm');
    //     $form->setAttributes(['action' => $this->url()->fromRoute('accounts/password-reset', ['token' => $token])]);
    //     $viewModel = new ViewModel([
    //         'form' => $form,
    //     ]);
    //     $request = $this->getRequest();
    //     if($request->isPost()) {
    //         $post = $request->getPost();
    //         $form->setData($post);
    //         if($form->isValid()) {
    //             $user = $this->entityManager->getRepository($this->options->getUserClass())->findOneBy(['registrationToken' => $token]);
    //             $user->setPassword(UserCredentialsService::encryptPassword($post['password']));
    //             $this->entityManager->persist($user);
    //             $this->entityManager->flush();
    //             return $viewModel->setTemplate($this->options->getPasswordResetSuccessfullyTemplate());
    //         }
    //     }
    //     return $viewModel->setTemplate($this->options->getPasswordResetTemplate());
    // }

    // public function changePassword()
    // {

    // }

    // public function resetPassword()
    // {

    // }

    // public function confirmEmailAction()
    // {
    //     $token = $this->params()->fromRoute('token');
    //     $user = $this->entityManager->getRepository($this->options->getUserClass())->findOneBy(['registrationToken' => $token]);
    //     $user->setState($this->entityManager->getRepository('VisoftMailerModule\Entity\ContactState')->findOneBy(['name' => 'Confirmed']));
    //     $this->entityManager->persist($user);
    //     $this->entityManager->flush();
    //     $viewModel = new ViewModel([]);
    //     // TODO: confirm email template should be as an option
    //     return $viewModel->setTemplate('fryday/registration/email-confirmed');       
    // }

    // public function runMysqlAction()
    // {
    //     $config = $this->getServiceLocator()->get('Config');
    //     $connectionParameters = $config['doctrine']['connection']['orm_default']['params'];
    //     var_dump($connectionParameters);
    //     // $script_path = 'module/Base/data/BaseData.sql';
    //     // $script_path = 'module/Fryday/data/IndustryData.sql';
    //     $command = null;
    //     if(file_exists($script_path)) {
    //         $command = "mysql -u{$connectionParameters['user']} -p{$connectionParameters['password']} -h{$connectionParameters['host']} -D{$connectionParameters['dbname']} < {$script_path}";
    //         shell_exec($command);
    //     }
    //     var_dump($command);
    // }
}