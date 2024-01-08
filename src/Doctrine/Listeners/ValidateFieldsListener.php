<?php

namespace Solutio\Doctrine\Listeners;

use Doctrine\ORM\Mapping as ORM;
use Solutio\Doctrine\AbstractEntity;
use Solutio\Utils\Data\StringManipulator;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;

class ValidateFieldsListener
{
  /** 
   * @ORM\PrePersist
   */
  public function prePersistHandler(AbstractEntity $entity, LifecycleEventArgs $event)
  {
    $validators = [];
    $className  = StringManipulator::GetInstance(get_class($entity))->replace('DoctrineORMModule\\\Proxy\\\__CG__\\\\', '')->toString();
    $metaData   = $event->getObjectManager()->getClassMetadata($className);
    $fields		  = $metaData->fieldMappings;
    if(count($fields) > 0){
      foreach($fields as $field){
        if(isset($field['nullable']) && !$field['nullable'] && $field['nullable'] !== null && (!isset($field['id']) || !$field['id'])) $validators[$field['fieldName']] = 'required';
      }
    }
    $maps 		= $metaData->getAssociationMappings();
    if(count($maps) > 0){
      $assocs   = [];
      foreach($maps as $fieldName => $field){
        if(($field['type'] == 1 || $field['type'] == 2) && isset($field['joinColumns'][0]) && isset($field['joinColumns'][0]['nullable']) && !$field['joinColumns'][0]['nullable'] && $field['joinColumns'][0]['nullable'] !== null) $validators[$fieldName] = 'required';
      }
    }
    if(count($validators) > 0){
      $values = $entity->toArray();
      foreach($validators as $fieldName => $validationType)
        if($validationType === 'required' && (!isset($values[$fieldName]) || (empty($values[$fieldName]) && $values[$fieldName] !== false && !is_numeric($values[$fieldName]))))
          throw new \InvalidArgumentException("The {$fieldName} field of {$className} is required.");
    }
  }
  
  /** 
   * @ORM\PreFlush
   */
  public function preFlushHandler(AbstractEntity $entity, PreFlushEventArgs $event)
  {
    $validators = [];
    $className  = StringManipulator::GetInstance(get_class($entity))->replace('DoctrineORMModule\\\Proxy\\\__CG__\\\\', '')->toString();
    $metaData   = $event->getEntityManager()->getClassMetadata($className);
    $fields		  = $metaData->fieldMappings;
    if(count($fields) > 0){
      foreach($fields as $field){
        if(isset($field['nullable']) && !$field['nullable'] && $field['nullable'] !== null && (!isset($field['id']) || !$field['id'])) $validators[$field['fieldName']] = 'required';
      }
    }
    $maps 		= $metaData->getAssociationMappings();
    if(count($maps) > 0){
      $assocs   = [];
      foreach($maps as $fieldName => $field){
        if(($field->type == 1 || $field->type == 2) && isset($field->joinColumns[0]) && isset($field->joinColumns[0]['nullable']) && !$field->joinColumns[0]['nullable'] && $field->joinColumns[0]['nullable'] !== null) $validators[$fieldName] = 'required';
      }
    }
    if(count($validators) > 0){
      $values = $entity->toArray();
      foreach($validators as $fieldName => $validationType)
        if($validationType === 'required' && (!isset($values[$fieldName]) || (empty($values[$fieldName]) && $values[$fieldName] !== false && !is_numeric($values[$fieldName]))))
          throw new \InvalidArgumentException("The {$fieldName} field of {$className} is required.");
    }
  }
}