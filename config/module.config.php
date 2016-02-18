<?php

namespace VisoftBaseModule;

return [    
    'oauth2' => [
        'facebook' => [
            'auth_uri' => 'https://www.facebook.com/dialog/oauth',
            'token_uri' => 'https://graph.facebook.com/oauth/access_token',
            'info_uri' => 'https://graph.facebook.com/me',
        ],
        'linkedin' => [
            'auth_uri' => 'https://www.linkedin.com/uas/oauth2/authorization',
            'token_uri' => 'https://www.linkedin.com/uas/oauth2/accessToken',
            'info_uri' => 'https://api.linkedin.com/v1/people/~',
        ],
    ],
    'doctrine_factories' => array(
        'authenticationadapter' => 'VisoftBaseModule\Factory\AuthenticationAdapterFactory',
    ),
    'doctrine' => array(
        'configuration' => array(
            'orm_default' => array(
                'generate_proxies' => true,
            ),
        ),
        'driver' => array(
            __NAMESPACE__ . '_driver' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(
                    __DIR__ . '/../src/' . __NAMESPACE__ . '/Entity',
                ),
            ),
            'orm_default' => array(
                'drivers' => array(
                    __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver',
                )
            )
        ),
        'authentication' => array(
            'orm_default' => array(
                'object_manager' => 'Doctrine\ORM\EntityManager',
                'identity_class' => 'VisoftBaseModule\Entity\UserInterface',
                'identity_property' => 'email',
                'credential_property' => 'password',
                'credential_callable' => 'VisoftBaseModule\Service\RegistrationService::verifyHashedPassword',
            ),
        ),
    ),
];