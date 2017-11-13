<?php

namespace Solutio\Doctrine\Listeners;

use Doctrine\ORM\Mapping as ORM;
use Solutio\AbstractEntity;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;

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
        if(($field['type'] == 1 || $field['type'] == 2) && $entity->{"get".ucfirst($fieldName)}()){
          $keys = $entity->{"get".ucfirst($fieldName)}()->getKeys();
          $obj  = $event->getEntityManager()->getReference($field['targetEntity'], $keys);
          $entity->{"set".ucfirst($fieldName)}($obj); 
        }
      }
    }
  }
  
  /** 
   * @ORM\PreFlush
   */
  public function preFlushHandler(AbstractEntity $entity, PreFlushEventArgs $event)
  {
    $className  = get_class($entity);
    $metaData   = $event->getEntityManager()->getClassMetadata($className);
    $maps 		  = $metaData->getAssociationMappings();
    if(count($maps) > 0){
      foreach($maps as $fieldName => $field){
        if(($field['type'] == 1 || $field['type'] == 2) && $entity->{"get".ucfirst($fieldName)}()){
          $keys = $entity->{"get".ucfirst($fieldName)}()->getKeys();
          $obj  = $event->getEntityManager()->getReference($field['targetEntity'], $keys);
          $entity->{"set".ucfirst($fieldName)}($obj); 
        }
      }
    }
  }
}