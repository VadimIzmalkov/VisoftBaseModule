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
                'Zend\Authentication\AuthenticationService' => function($serviceLocator) {
                    $doctrineAuthenticationService = $serviceLocator->get('doctrine.authenticationservice.orm_default');
                    return $doctrineAuthenticationService;
                },
                'VisoftBaseModule\Options\ModuleOptions' => function($serviceLocator) {
                    $config = $serviceLocator->get('Config');
                    return new Options\ModuleOptions(isset($config['visoftbasemodule']) ? $config['visoftbasemodule'] : []);
                },
                'VisoftBaseModule\Options\OAuth2Options' => function($serviceLocator){
                    $config = $serviceLocator->get('Config');
                    return new Options\OAuth2Options(isset($config['oauth2']['facebook']) ? $config['oauth2']['facebook'] : []);
                },
                'VisoftBaseModule\Service\UserService' => function($serviceLocator) {
                    $entityManager = $serviceLocator->get('Doctrine\ORM\EntityManager');
                    $moduleOptions = $serviceLocator->get('VisoftBaseModule\Options\ModuleOptions');
                    return new Service\UserService($entityManager, $moduleOptions);
                },
                'VisoftBaseModule\Service\OAuth2\FacebookClient' => function($serviceLocator) {
                    $config = $serviceLocator->get('Config');
                    $entityManager = $serviceLocator->get('Doctrine\ORM\EntityManager');
                    $oAuth2Options = $serviceLocator->get('VisoftBaseModule\Options\OAuth2Options');
                    $facebookClient = new Service\OAuth2\FacebookClient($entityManager);
                    $facebookClient->setOptions($oAuth2Options);
                    return $facebookClient;
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
                }
            ],
        ];
    }
    //         'factories' => array(
    //             // 'VisoftMailerModule\Controller\Contact' => function(ControllerManager $controllerManager) {
    //             //     $serviceLocator = $controllerManager->getServiceLocator();
    //             //     $entityManager = $serviceLocator->get('Doctrine\ORM\EntityManager');
    //             //     $moduleOptions = $serviceLocator->get('VisoftMailerModule\Options\ModuleOptions');
    //             //     $contactService = $serviceLocator->get('VisoftMailerModule\Service\ContactService');
    //             //     $processingService = $serviceLocator->get('VisoftBaseModule\Service\ProcessingService');
    //             //     return new Controller\ContactController($entityManager, $contactService, $moduleOptions, $processingService);
    //             // },
    //             // 'VisoftMailerModule\Controller\Mailer' => function(ControllerManager $controllerManager) {
    //             //     $serviceLocator = $controllerManager->getServiceLocator();
    //             //     $entityManager = $serviceLocator->get('Doctrine\ORM\EntityManager');
    //             //     $moduleOptions = $serviceLocator->get('VisoftMailerModule\Options\ModuleOptions');
    //             //     $processingService = $serviceLocator->get('VisoftBaseModule\Service\ProcessingService');
    //             //     $mailerService = $serviceLocator->get('VisoftMailerModule\Service\MailerService');
    //             //     return new Controller\MailerController($entityManager, $mailerService, $moduleOptions, $processingService);
    //             // },
    // }
}