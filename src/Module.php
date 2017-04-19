<?php

namespace Solutio;

use Zend\Mvc\MvcEvent,
    Solutio\Utils\Data\ArrayObject;

class Module
{
    
  const VERSION = '1.0.9';
  
  public function onBootstrap(MvcEvent $e)
  {
    $eventManager       = $e->getApplication()->getEventManager();

    $sys                = new ArrayObject;
    $sys['directory']		= $_SERVER['DOCUMENT_ROOT'];
    if($sys['directory'][strlen($sys['directory'])-1] != "/")
      $sys['directory'] .= '/';
    $protocol           = explode('/', $_SERVER['SERVER_PROTOCOL']);
    $sys['protocol']    = strtolower($protocol[0]);
    $sys['url']         = $sys['protocol']."://".$_SERVER['HTTP_HOST']."/";

    System::SetSystem((array) $sys);
  }
  
  public function getServiceConfig()
  {
    return [
      'factories' => [
        'Solutio\Doctrine\EntityService' => function($container) {
          return new Doctrine\EntityService($container->get('Doctrine\ORM\EntityManager'));
        }
      ]
    ];
  }
    
}