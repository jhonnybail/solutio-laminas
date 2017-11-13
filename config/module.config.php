<?php

namespace Solutio;

return [
  'solutio' => [
    'cors'  => [
      "origin"          => ["*"],
      "methods"         => ["*"],
      "headers.allow"   => [],
      "credentials"     => false,
      "cache"           => -1,
    ]
  ],
  'doctrine' => [
    'driver' => [
      'cache' => [
        'class' => 'Doctrine\Common\Cache\ApcCache'
      ],
      'configuration' => [
        'orm_default' => [
          'metadata_cache'        => 'apc',
          'query_cache'           => 'apc',
          'result_cache'          => 'apc'
        ]
      ],
      'SolutioConfig_driver' => [
        'class' => \Doctrine\ORM\Mapping\Driver\AnnotationDriver::class,
        'cache' => 'array',
        'paths' => [
          __DIR__.'/../src'
        ]
      ],
      'orm_default' => [
        'drivers' => [
          'Solutio' => 'SolutioConfig_driver'
        ]
      ]
    ],
    'configuration' => [
        'orm_default' => [
            'default_repository_class_name' => Doctrine\EntityRepository::class,
            'customHydrationModes'  => [
               'SolutioArrayHydrator' => Doctrine\Hydrators\ArrayHydrator::class,
            ],
        ]
    ]
  ],
  'service_listener'  => [
    Service\Listener\RemoveChildrenPendingListener::class  => Factory\ServiceListenerWithContainerFactory::class
  ],
  'view_manager' => [
    'strategies' => [
      'ViewJsonStrategy',
    ]
  ],
];
