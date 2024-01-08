<?php

namespace Solutio\Doctrine\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface,
    Laminas\EventManager\EventManager,
    Interop\Container\ContainerInterface,
    Solutio\Utils\Data\StringManipulator,
    Solutio\Doctrine\Service\TriggerMethodServiceDecorator,
    Solutio\Doctrine\Service\EntityService;

class EntityServiceFactory implements FactoryInterface
{
  public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
  {
    $em         = $container->get('Doctrine\ORM\EntityManager');
    $className  = $this->extractClassName($requestedName);
    if(class_exists($requestedName))
      $service = new TriggerMethodServiceDecorator(new $requestedName($em->getRepository($className)));
    else
      $service = new TriggerMethodServiceDecorator(new EntityService($em->getRepository($className)));
    $service->setEventManager(new EventManager($container->get('Laminas\EventManager\SharedEventManager')));
    $service->getEventManager()->addIdentifiers([$requestedName]);
    return $service;
  }
  
  private function extractClassName($requestedName){
    return (string) StringManipulator::GetInstance($requestedName)->replace('\\\Service', '')->replace('Service', '');
  }
}