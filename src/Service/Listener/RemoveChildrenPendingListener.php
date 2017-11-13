<?php

namespace Solutio\Service\Listener;

use Zend\ServiceManager\ServiceManager;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Solutio\Utils\Data\StringManipulator;

class RemoveChildrenPendingListener implements ListenerAggregateInterface
{
  private $listeners = [];
  private $container;
  
  public function __construct(ServiceManager $container)
  {
    $this->container  = $container;
  }

  public function attach(EventManagerInterface $events, $priority = 1)
  {
      $this->listeners[] = $events->getSharedManager()->attach(\Solutio\Service\EntityService::class, 'after.update', [$this, 'remove'], $priority);
  }

  public function detach(EventManagerInterface $events)
  {
      foreach ($this->listeners as $index => $listener) {
          $events->detach($listener);
          unset($this->listeners[$index]);
      }
  }

  public function remove(EventInterface $event)
  {
      $entity = $event->getParams()[0];
      foreach($entity->getChildrenPendingRemovation() as $className => $list){
        try{
          $service  = $this->container->build($className);
        }catch(\Exception $e){
          $div            = (new StringManipulator($className))->split('\\');
          $total          = $div->count();
          $div[]          = $div->end() . 'Service';
          $div[$total-1]  = 'Service';
          $service        = new StringManipulator;
          $div->every(function($value) use ($service){
            $service->concat($value . '\\');
            return true;
          });
          $service        = $this->container->build($service->substr(0, -1)->toString());
        }
        foreach($list as $child){
          $service->delete($child);
        }
      }
  }
}