<?php

namespace Solutio\Doctrine\Listeners;

use Doctrine\ORM\Mapping as ORM;
use Solutio\AbstractEntity;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

class ValidateFieldsListener
{
  /** 
   * @ORM\PrePersist
   */
  public function prePersistHandler(AbstractEntity $entity, LifecycleEventArgs $event)
  {
    $validators = [];
    $className  = get_class($entity);
    $metaData   = $event->getEntityManager()->getClassMetadata($className);
    $fields		  = $metaData->fieldMappings;
    if(count($fields) > 0){
      foreach($fields as $field){
        if(!$field['nullable'] && !$field['id']) $validators[$field['fieldName']] = 'required';
      }
    }
    $maps 		= $metaData->getAssociationMappings();
    if(count($maps) > 0){
      $assocs   = [];
      foreach($maps as $fieldName => $field){
        if(($field['type'] == 1 || $field['type'] == 2) && !$field['joinColumns'][0]['nullable']) $validators[$fieldName] = 'required';
      }
    }
    if(count($validators) > 0){
      $values = $entity->toArray();
      foreach($validators as $fieldName => $validationType)
        if($validationType === 'required' && empty($values[$fieldName]))
          throw new \InvalidArgumentException("The {$fieldName} field of {$className} is required.");
    }
  }
}