<?php

namespace Solutio;

return [
  'solutio' => [
    'logs'  => [
      'doctrine'  => [
        'active'  => false,
        'path'    => null
      ]
    ],
    'cors'  => [
      "origin"          => ["*"],
      "methods"         => ["*"],
      "headers.allow"   => [],
      "credentials"     => false,
      "cache"           => -1,
    ],
    'cache' => [
      'controller' => [
        'default' => [
          'enabled' => true,
          'adapter' => [
            'name'    => 'apcu',
            'options' => [
              'ttl' => 180
            ]
          ]
        ]
      ]
    ]
  ],
  'doctrine' => [
    'driver' => [
      'configuration' => [
        'orm_default' => [
          'metadata_cache'        => 'apcu',
          'query_cache'           => 'apcu',
          'result_cache'          => 'apcu',
          'hydration_cache'       => 'apcu'
        ]
      ],
      'SolutioConfig_driver' => [
        'class' => \Doctrine\ORM\Mapping\Driver\AnnotationDriver::class,
        'paths' => [
          __DIR__ . '/../src'
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
        'metadata_cache'        => 'apcu',
        'query_cache'           => 'apcu',
        'result_cache'          => 'apcu',
        'hydration_cache'       => 'apcu',
        'second_level_cache'    => [
          'enabled'               => true,
          'default_lifetime'      => 300,
          'default_lock_lifetime' => 180,
          'file_lock_region_directory' => __DIR__ . '/../../../data/DoctrineORMModule/FileLock',
          'regions' => [
            'Solutio\Simple' => [
              'lifetime'      => 300,
              'lock_lifetime' => 180,
            ],
          ],
        ],
      ]
    ]
  ],
  'service_listener'  => [
    Doctrine\Service\Listener\RemoveChildrenPendingListener::class  => Factory\ServiceListenerFactory::class,
    Service\Listener\CheckAuthorizationListener::class              => Factory\ServiceListenerFactory::class
  ],
  'service_manager' => [
    'factories' => [
      \Solutio\View\Renderer\JsonStringRenderer::class => \Zend\ServiceManager\Factory\InvokableFactory::class,
      'ViewJsonStringStrategy' => Mvc\Service\ViewJsonStringStrategyFactory::class
    ],
  ],
  'view_manager' => [
    'strategies' => [
      'ViewJsonStrategy',
      'ViewJsonStringStrategy'
    ]
  ]
];
