<?php

namespace Solutio\Doctrine\Service\Listener;

use Laminas\EventManager\EventInterface;
use Solutio\Service\Listener\AbstractServiceListener;
use Solutio\Utils\Data\StringManipulator;
use Doctrine\ORM\Mapping as ORM;

class RemoveChildrenPendingListener extends AbstractServiceListener
{
  public function makeListeners($priority = 1)
  {
    $this->listeners[] = $this->getEventManager()->getSharedManager()->attach(\Solutio\Service\AbstractService::class, 'before.update', [$this, 'remove'], 50);
  }
  
  public function remove(EventInterface $event)
  {
    $entity       = $event->getParams()[0];
    $reflection   = \Laminas\Server\Reflection::reflectClass($entity);
    $annotation   = new \Doctrine\Common\Annotations\AnnotationReader;
    foreach($entity->getChildrenPendingRemovation() as $className => $properties){
      foreach($properties as $name => $property){
        if($property['options']['propertyAnnotation'] instanceof ORM\OneToMany){
          $service    = null;
          $hasRemove  = false;
          try{
            $service  = $this->getContainer()->get($className);
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
              $service        = $this->getContainer()->get($service->substr(0, -1)->toString());
            }catch(\Exception $e){continue;}
          }
          foreach($property['entities'] as $child){
            if($service instanceof \Solutio\Service\EntityService){
              $service->delete($child);
            }
          }
        }elseif($property['options']['propertyAnnotation'] instanceof ORM\ManyToMany){
          $findedEntity = $event->getTarget()->getRepository()->findById($entity->getKeys());
          $method = StringManipulator::GetInstance('remove' . ucfirst($name));
          if($method->substr($method->length()-3, 3)->toString() === 'ies')
            $method = $method->substr(0, -3)->concat('y')->toString();
          elseif($method->substr($method->length()-3, 3)->toString() === 'ses')
            $method = $method->substr(0, -2)->toString();
          else
            $method = $method->substr(0, -1)->toString();
          foreach($property['entities'] as $child){
            $findedEntity->{$method}($child);
          }
        }
      }
    }
  }
}