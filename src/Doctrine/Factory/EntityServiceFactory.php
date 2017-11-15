<?php

namespace Solutio\Doctrine\Factory;

use Zend\ServiceManager\Factory\FactoryInterface,
    Zend\EventManager\EventManager,
    Interop\Container\ContainerInterface,
    Solutio\Utils\Data\StringManipulator,
    Solutio\Doctrine\Service\EntityService;

class EntityServiceFactory implements FactoryInterface
{
  public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
  {
    $em         = $container->get('Doctrine\ORM\EntityManager');
    $className  = $this->extractClassName($requestedName);
    if(class_exists($requestedName))
      return new $requestedName($em->getRepository($className));
    $service  = new EntityService($em->getRepository($className));
    $service->setEventManager(new EventManager($container->get('Zend\EventManager\SharedEventManager')));
    $service->getEventManager()->addIdentifiers([$requestedName]);
    return $service;
  }
  
  private function extractClassName($requestedName){
    return (string) StringManipulator::GetInstance($requestedName)->replace('\\\Service', '')->replace('Service', '');
  }
}