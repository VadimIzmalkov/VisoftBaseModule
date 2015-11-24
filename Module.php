<?php

namespace VisoftBaseModule;

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
                'VisoftBaseModule\Options\ModuleOptions' => function($serviceLocator) {
                    $config = $serviceLocator->get('Config');
                    return new Options\ModuleOptions(isset($config['visoftbasemodule']) ? $config['visoftbasemodule'] : []);
                },
                'VisoftBaseModule\Service\UserService' => function($serviceLocator) {
                    $entityManager = $serviceLocator->get('Doctrine\ORM\EntityManager');
                    $moduleOptions = $serviceLocator->get('VisoftBaseModule\Options\ModuleOptions');
                    return new Service\UserService($entityManager, $moduleOptions);
                },
            ],
        ];
    }
}