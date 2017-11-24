<?php

namespace Solutio\Doctrine\Factory;

use Zend\ServiceManager\Factory\FactoryInterface,
    Zend\Cache\StorageFactory,
    Interop\Container\ContainerInterface,
    Solutio\Utils\Data\StringManipulator,
    Solutio\Controller\ServiceRestController;

class ServiceRestControllerFactory implements FactoryInterface
{
  public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
  {
    $serviceClassName  = $this->extractServiceClassName($requestedName);
    if(!$container->has($serviceClassName))
      $service = (new EntityServiceFactory)($container, $serviceClassName);
    else
      $service = $container->get($serviceClassName);
      
    if(class_exists($requestedName))
      $controller = new $requestedName($service);
    else
      $controller = new ServiceRestController($service);
      
    $config   = $container->get('application')->getConfig()['solutio']['cache']['controller'];
    $options  = $config['default'];
    if(isset($config[$requestedName]))
      $options = array_replace_recursive($options, $config[$requestedName]);
      
    $controller->setCacheable($options['enabled']);
    $controller->setCacheAdapter(StorageFactory::factory(['adapter' => $options['adapter']]));
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