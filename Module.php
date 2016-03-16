<?php

namespace VisoftBaseModule;

use Zend\Mvc\Controller\ControllerManager;

class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
	
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getServiceConfig() 
    {
        return [
            'factories' => [
                // overriding default Zend Authentication Service by Doctrine Authentication Service
                'Zend\Authentication\AuthenticationService' => function($serviceLocator) {
                    $doctrineAuthenticationService = $serviceLocator->get('doctrine.authenticationservice.orm_default');
                    return $doctrineAuthenticationService;
                },
                'VisoftBaseModule\Options\ModuleOptions' => function($serviceLocator) {
                    $config = $serviceLocator->get('Config');
                    return new Options\ModuleOptions(isset($config['visoftbasemodule']) ? $config['visoftbasemodule'] : []);
                },
                'VisoftBaseModule\Service\UserService' => function($serviceLocator) {
                    $entityManager = $serviceLocator->get('Doctrine\ORM\EntityManager');
                    $moduleOptions = $serviceLocator->get('VisoftBaseModule\Options\ModuleOptions');
                    return new Service\UserService($entityManager, $moduleOptions);
                },
                'VisoftBaseModule\Options\OAuth2FacebookOptions' => function($serviceLocator){
                    $config = $serviceLocator->get('Config');
                    return new Options\OAuth2Options(isset($config['oauth2']['facebook']) ? $config['oauth2']['facebook'] : []);
                },
                'VisoftBaseModule\Options\OAuth2LinkedInOptions' => function($serviceLocator){
                    $config = $serviceLocator->get('Config');
                    return new Options\OAuth2Options(isset($config['oauth2']['linkedin']) ? $config['oauth2']['linkedin'] : []);
                },
                'VisoftBaseModule\Service\OAuth2\FacebookClient' => function($serviceLocator) {
                    $config = $serviceLocator->get('Config');
                    $entityManager = $serviceLocator->get('Doctrine\ORM\EntityManager');
                    $oAuth2Options = $serviceLocator->get('VisoftBaseModule\Options\OAuth2FacebookOptions');
                    $client = new Service\OAuth2\FacebookClient($entityManager);
                    $client->setOptions($oAuth2Options);
                    return $client;
                },
                'VisoftBaseModule\Service\OAuth2\LinkedInClient' => function($serviceLocator) {
                    $config = $serviceLocator->get('Config');
                    $entityManager = $serviceLocator->get('Doctrine\ORM\EntityManager');
                    $oAuth2Options = $serviceLocator->get('VisoftBaseModule\Options\OAuth2LinkedInOptions');
                    $client = new Service\OAuth2\LinkedInClient($entityManager);
                    $client->setOptions($oAuth2Options);
                    return $client;
                },
                'VisoftBaseModule\Service\Authorization\Acl\Acl' => function($serviceLocator) {
                    $config = $serviceLocator->get('Config');
                    return new Service\Authorization\Acl\Acl($config);
                },

            ],
        ];
    }

    public function getControllerConfig() 
    {
        return [
            'factories' => [
                'VisoftBaseModule\Controller\OAuth2' => function(ControllerManager $controllerManager) {
                    $serviceLocator = $controllerManager->getServiceLocator();
                    $authenticationService = $serviceLocator->get('Zend\Authentication\AuthenticationService');
                    $moduleOptions = $serviceLocator->get('VisoftBaseModule\Options\ModuleOptions');
                    return new Controller\OAuth2Controller($authenticationService, $moduleOptions);
                },
                'VisoftBaseModule\Service\Authentication\Controller\Authentication' => function(ControllerManager $controllerManager) {
                    $serviceLocator = $controllerManager->getServiceLocator();
                    $moduleOptions = $serviceLocator->get('VisoftBaseModule\Options\ModuleOptions');
                    $entityManager = $serviceLocator->get('Doctrine\ORM\EntityManager');
                    $authenticationService = $serviceLocator->get('Zend\Authentication\AuthenticationService');
                    return new Service\Authentication\Controller\AuthenticationController($entityManager, $authenticationService, $moduleOptions);
                },
            ],
            'abstract_factories' => [
                'VisoftBaseModule\Controller\Factory\AbstractCrudControllerFactory',
            ],
        ];
    }

    public function getControllerPluginConfig()
    {
        return array(
            'factories' => array(
                'UserActivityLogger' => function($serviceLocator) {
                    $parentLocator = $serviceLocator->getServiceLocator();
                    $entityManager = $parentLocator->get('Doctrine\ORM\EntityManager');
                    return new Service\Log\Controller\Plugin\UserActivityLogger($entityManager);
                },
                'Social' => function($serviceLocator) {
                    $parentLocator = $serviceLocator->getServiceLocator();
                    $socialClients['facebook'] = $parentLocator->get('VisoftBaseModule\Service\OAuth2\FacebookClient');
                    $socialClients['linkedin'] = $parentLocator->get('VisoftBaseModule\Service\OAuth2\LinkedInClient');
                    return new Controller\Plugin\Social($socialClients);
                },
                'Authentication' => function($serviceLocator) {
                    $parentLocator = $serviceLocator->getServiceLocator();
                    $entityManager = $parentLocator->get('Doctrine\ORM\EntityManager');
                    $authenticationService = $parentLocator->get('Zend\Authentication\AuthenticationService');
                    return new Service\Authentication\Controller\Plugin\Authentication($entityManager, $authenticationService);
                },
                'accessoryPlugin' => function($serviceLocator) {
                    return new Controller\Plugin\AccessoryPlugin();
                }
            ),
            'invokables' => [
                'checkDir' => 'VisoftBaseModule\Controller\Plugin\CheckDir',
                'downloadFile' => 'VisoftBaseModule\Controller\Plugin\DownloadFile',
            ],
        );
    }

    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
                // This will overwrite the native navigation helper
                'navigation' => function(\Zend\View\HelperPluginManager $pm) {
                    $sm = $pm->getServiceLocator();
                    $config = $sm->get('Config');
                    // // Setup ACL:
                    $acl = new \VisoftBaseModule\Service\Authorization\Acl\Acl($config);

                    // // Get the AuthenticationService
                    $auth = $sm->get('Zend\Authentication\AuthenticationService');
                    $role = \VisoftBaseModule\Service\Authorization\Acl\Acl::DEFAULT_ROLE;

                    if ($auth->hasIdentity())
                        $role = $auth->getIdentity()->getRole()->getName();

                    // Get an instance of the proxy helper
                    $navigation = $pm->get('Zend\View\Helper\Navigation');
                    // TODO: ServiceLocator Should set automaticaly!!!!!
                    // Why needs this line??? FIX IT!
                    $navigation->setServiceLocator($sm);
                    
                    // Store ACL and role in the proxy helper:
                    $navigation->setAcl($acl)->setRole($role); // 'member'
                    
                    // Return the new navigation helper instance
                    return $navigation;
                },

                'oauth2uri' => function(\Zend\View\HelperPluginManager $pluginManager) {
                    $serviceLocator = $pluginManager->getServiceLocator();
                    $socialClients['facebook'] = $serviceLocator->get('VisoftBaseModule\Service\OAuth2\FacebookClient');
                    $socialClients['linkedin'] = $serviceLocator->get('VisoftBaseModule\Service\OAuth2\LinkedInClient');
                    $helper = new Service\OAuth2\View\Helper\OAuth2UriHelper($socialClients);
                    return $helper;
                }
            ),
        );
    }

    // Attach event listeners
    public function onBootstrap(\Zend\EventManager\EventInterface $e) 
    { 
        $application = $e->getApplication();
        $em = $application->getEventManager();
        $em->attach('route', array($this, 'onRoute'), -100);
    }

    public function onRoute(\Zend\EventManager\EventInterface $e) 
    {         

        $application = $e->getApplication();
        $routeMatch = $e->getRouteMatch();
        $serviceManader = $application->getServiceManager();
        $authenticationService = $serviceManader->get('Zend\Authentication\AuthenticationService');
        $acl = $serviceManader->get('VisoftBaseModule\Service\Authorization\Acl\Acl');
        
        // everyone is guest until logging in
        $role = \VisoftBaseModule\Service\Authorization\Acl\Acl::DEFAULT_ROLE; // The default role is guest $acl

        // get role if user logged in
        if ($authenticationService->hasIdentity()) {
            $user = $authenticationService->getIdentity();
            $role = $user->getRole()->getName();
        }

        // requested route
        $controller = $routeMatch->getParam('controller');
        $action = $routeMatch->getParam('action');
        $params = $routeMatch->getParams();

        if (!$acl->hasResource($controller)) {
            throw new \Exception('Resource ' . $controller . ' not defined in ACL');
        }

        
        if (!$acl->isAllowed($role, $controller, $action)) {

            $response = $e->getResponse();
            $requestedUri = $e->getRequest()->getRequestUri();
            $config = $serviceManader->get('config');
            $redirect_route = $config['acl']['redirect_route'];
            if(!empty($redirect_route)) {
                // TODO: FIXIT
                $url = $e->getRouter()->assemble($redirect_route['params'], $redirect_route['options']);
                $response->getHeaders()->addHeaderLine('Location', $url);

                // The HTTP response status code 302 Found is a common way of performing a redirection.
                // http://en.wikipedia.org/wiki/HTTP_302
                $response->setStatusCode(302);
                $headers = $response->getHeaders();
                $cookie = new \Zend\Http\Header\SetCookie('requestedUri', $requestedUri, time() + 60, '/');
                $headers->addHeader($cookie);
                $response->sendHeaders();    
                exit;
            } else {
                // Status code 403 responses are the result of the web server being configured to deny access,
                // for some reason, to the requested resource by the client.
                // http://en.wikipedia.org/wiki/HTTP_403
                $response->setStatusCode(403);
                $response->setContent('
                    <html>
                        <head>
                            <title>403 Forbidden</title>
                        </head>
                        <body>
                            <h1>403 Forbidden</h1>
                        </body>
                    </html>'
                );
                return $response;
            }
        }
    }
}