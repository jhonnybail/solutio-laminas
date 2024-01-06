<?php

namespace Solutio\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface,
    Interop\Container\ContainerInterface;

class ServiceListenerFactory implements FactoryInterface
{
  public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
  {
    $listener     = new $requestedName($container);
    $eventManager = $container->get('Laminas\EventManager\EventManagerInterface');
    $listener->attach($eventManager);
  }
}