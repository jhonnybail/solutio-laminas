<?php

namespace Solutio;

return [
  'solutio' => [
    'cors'  => [
      "origin"          => ["*"],
      "methods"         => ["GET", "POST", "PUT", "PATCH", "DELETE"],
      "headers.allow"   => [],
      "headers.expose"  => [],
      "credentials"     => false,
      "cache"           => 0,
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
  ]
];
