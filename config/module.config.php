<?php

namespace VisoftBaseModule;

return [    
    'oauth2' => [
        'facebook' => [
            'auth_uri' => 'https://www.facebook.com/dialog/oauth',
            'token_uri' => 'https://graph.facebook.com/oauth/access_token',
            'info_uri' => 'https://graph.facebook.com/me',
        ],
    ],
    // 'Zend\Authentication\AuthenticationService' => 'Base\Factory\Service\AuthenticationServiceFactory',
    'doctrine_factories' => array(
        'authenticationadapter' => 'VisoftBaseModule\Factory\AuthenticationAdapterFactory',
    ),
    'doctrine' => array(
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
    ),
    // 'router' => [
    //     'routes' => [
    //         'visoft-authentication' => [
    //             'type' => 'Literal',
    //             'options' => [
    //                 'route' => '/visoft-authentication',
    //                 'defaults' => [
    //                     'controller' => 'Base\Controller\Index',
    //                     'action' => 'index',
    //                 ],
    //             ],
    //             'may_terminate' => true,
    //             'child_routes' => array(
    //                 // 'default' => array(
    //                 //     'type'    => 'Segment',
    //                 //     'options' => array(
    //                 //         'route'    => '/',
    //                 //         'defaults' => array(
    //                 //             'controller' => 'Base\Controller\Index',
    //                 //             'action' => 'Index',
    //                 //         ),
    //                 //     ),
    //                 // ),
    //                 'oauth2-callback' => array(
    //                     'type'    => 'Segment',
    //                     'options' => array(
    //                         'route'    => '/oauth2-callback/:provider[/]',
    //                         'defaults' => array(
    //                             'controller' => 'VisoftBaseModule\Controller\OAuth2',
    //                             'action' => 'oauth2-callback',
    //                         ),
    //                     ),
    //                 ),
    //             ),
    //         ],
    //     ],
    // ],
];