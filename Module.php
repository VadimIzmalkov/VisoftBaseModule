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
                    // var_dump($oAuth2Options);
                    // die('gg');
                    $client = new Service\OAuth2\LinkedInClient($entityManager);
                    $client->setOptions($oAuth2Options);
                    return $client;
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
            ),
            'invokables' => [
                'checkDir' => 'VisoftBaseModule\Controller\Plugin\CheckDir',
            ],
        );
    }
}