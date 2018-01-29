<?php

namespace Solutio\Factory;

use Zend\ServiceManager\Factory\FactoryInterface,
    Interop\Container\ContainerInterface,
    Solutio\Utils\Data\StringManipulator,
    Solutio\Controller\ServiceRestController;

class ServiceRestControllerFactory implements FactoryInterface
{
  public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
  {
    $serviceClassName   = $this->extractServiceClassName($requestedName);
    $service            = $container->get($serviceClassName);
      
    if(class_exists($requestedName))
      $controller = new $requestedName($service);
    else
      $controller = new ServiceRestController($service);
    return $controller;
  }
  
  private function extractServiceClassName($requestedName){
    return (string) StringManipulator::GetInstance($requestedName)->replace('Controller', 'Service')->replace('Rest', '');
  }
  
  private function generateAliasName($requestedName){
    return (string) StringManipulator::GetInstance($requestedName)
                        ->split('\\')
                        ->end()
                        ->replace('RestController', '')
                        ->replace('Controller', '')
                        ->replace('(\D)([A-Z])', '\1-\2')
                        ->toLowerCase();
  }
}