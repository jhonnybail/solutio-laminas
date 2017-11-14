<?php

namespace Solutio\Factory;

use Zend\ServiceManager\Factory\FactoryInterface,
    Interop\Container\ContainerInterface;

class ServiceListenerFactory implements FactoryInterface
{
  public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
  {
    $listener     = new $requestedName($container);
    $eventManager = $container->get('Zend\EventManager\EventManagerInterface');
    $listener->attach($eventManager);
  }
}