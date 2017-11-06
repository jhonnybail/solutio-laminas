<?php

namespace Solutio\Doctrine\Listeners;

use Doctrine\ORM\Mapping as ORM;
use Lubro\Sistema\Entities\AbstractEntity;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

class MappingsReferenceListener
{
  /** 
   * @ORM\PrePersist
   */
  public function prePersistHandler(AbstractEntity $entity, LifecycleEventArgs $event)
  {
    $className  = get_class($entity);
    $metaData   = $event->getEntityManager()->getClassMetadata($className);
    $maps 		  = $metaData->getAssociationMappings();
    if(count($maps) > 0){
      foreach($maps as $fieldName => $field){
        if(($field['type'] == 1 || $field['type'] == 2) && method_exists($entity, "get".ucfirst($fieldName)) && $entity->{"get".ucfirst($fieldName)}()){
          $keys = $entity->{"get".ucfirst($fieldName)}()->getKeys();
          $obj  = $this->getEntityManager()->getReference($field['targetEntity'], $keys);
          $entity->{"set".ucfirst($fieldName)}($obj); 
        }
      }
    }
  }
}