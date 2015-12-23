<?php

namespace VisoftBaseModule\Factory;
 
use DoctrineModule\Service\Authentication\AdapterFactory;

use Zend\ServiceManager\ServiceLocatorInterface;

use VisoftBaseModule\Adapter\AuthenticationAdapter;
 
class AuthenticationAdapterFactory extends AdapterFactory
{
    /**
     * {@inheritDoc}
     *
     * @return \MyDoctrineAuth\Adapter\ObjectRepository
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /* @var $options \DoctrineModule\Options\Authentication */
        $options = $this->getOptions($serviceLocator, 'authentication');
 
        if (is_string($objectManager = $options->getObjectManager())) {
            $options->setObjectManager($serviceLocator->get($objectManager));
        }
 
        return new AuthenticationAdapter($options);
    }
}