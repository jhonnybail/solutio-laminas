<?php

namespace Solutio\Service\Listener;

use Zend\EventManager\EventInterface;
use Solutio\Utils\Data\StringManipulator;

class RemoveChildrenPendingListener extends AbstractServiceListener
{
  public function makeListeners($priority = 1)
  {
    $this->listeners[] = $this->getEventManager()->getSharedManager()->attach(\Solutio\Service\EntityService::class, 'after.update', [$this, 'remove'], $priority);
  }
  
  public function remove(EventInterface $event)
  {
    $entity = $event->getParams()[0];
    foreach($entity->getChildrenPendingRemovation() as $className => $list){
      $service = null;
      try{
        $service  = $this->getContainer()->build($className);
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
        try{
          $service        = $this->getContainer()->build($service->substr(0, -1)->toString());
        }catch(\Exception $e){continue;}
      }
      foreach($list as $child){
        $service->delete($child);
      }
    }
  }
}