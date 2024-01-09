<?php

namespace Solutio\Doctrine\Listeners;

use Doctrine\ORM\Mapping as ORM;
use Solutio\Doctrine\AbstractEntity;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;

class MappingsReferenceListener
{
  /** 
   * @ORM\PrePersist
   */
  public function prePersistHandler(AbstractEntity $entity, LifecycleEventArgs $event)
  {
    $className  = get_class($entity);
    $em         = $event->getObjectManager();
    $metaData   = $em->getClassMetadata($className);
    $maps 		  = $metaData->getAssociationMappings();
    if(count($maps) > 0){
      foreach($maps as $fieldName => $field){
        if(($field['type'] == 1 || $field['type'] == 2)){
          if(!empty($entity->{"get".ucfirst($fieldName)}())){
            $keys = $entity->{"get".ucfirst($fieldName)}()->getKeys();
            try{
              if(\Doctrine\ORM\UnitOfWork::STATE_NEW !== $em->getUnitOfWork()->getEntityState($entity->{"get".ucfirst($fieldName)}())){
                $obj  = $event->getObjectManager()->getReference($field['targetEntity'], $keys);
                if($obj instanceof AbstractEntity){
                  $obj->getKeys();
                  $entity->{"set".ucfirst($fieldName)}($obj);
                }else{
                  $entity->{"set".ucfirst($fieldName)}(null);
                }
              }else{
                foreach($keys as $key)
                  if(empty($key) && $key !== null){
                    $entity->{"set".ucfirst($fieldName)}(null);
                    break;
                  }
              }
            }catch(\Doctrine\ORM\ORMException $e){
              $entity->{"set".ucfirst($fieldName)}(null);
            }catch(\Exception $e){} 
          }elseif($entity->{"get".ucfirst($fieldName)}() === ""){
            $entity->{"set".ucfirst($fieldName)}(null);
          }
        }elseif($field['type'] === 8 && $list = $entity->{"get".ucfirst($fieldName)}()){
          foreach($list as $k => $obj){
            $keys = $obj->getKeys();
            try{
              $obj  = $event->getObjectManager()->getReference($field['targetEntity'], $keys);
              if($obj instanceof AbstractEntity){
                $obj->getKeys();
                $list[$k] = $obj;
              }else
                $list[$k] = null;
            }catch(\Exception $e){}
          }
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
    $em         = $event->getEntityManager();
    $metaData   = $em->getClassMetadata($className);
    $maps 		  = $metaData->getAssociationMappings();
    if(count($maps) > 0){
      foreach($maps as $fieldName => $field){
        if(($field['type'] == 1 || $field['type'] == 2)){
          if(!empty($entity->{"get".ucfirst($fieldName)}())){
            $keys = $entity->{"get".ucfirst($fieldName)}()->getKeys();
            try{
              if(\Doctrine\ORM\UnitOfWork::STATE_NEW !== $em->getUnitOfWork()->getEntityState($entity->{"get".ucfirst($fieldName)}())){
                $obj  = $event->getEntityManager()->getReference($field['targetEntity'], $keys);
                if($obj instanceof AbstractEntity){
                  $obj->getKeys();
                  $entity->{"set".ucfirst($fieldName)}($obj);
                }else{
                  $entity->{"set".ucfirst($fieldName)}(null);
                }
              }else{
                foreach($keys as $key)
                  if(empty($key) && $key !== null){
                    $entity->{"set".ucfirst($fieldName)}(null);
                    break;
                  }
              }
            }catch(\Doctrine\ORM\ORMException $e){
              $entity->{"set".ucfirst($fieldName)}(null);
            }catch(\Exception $e){}  
          }elseif($entity->{"get".ucfirst($fieldName)}() === ""){
            $entity->{"set".ucfirst($fieldName)}(null);
          }
        }elseif(($field['type'] === 8 || $field['type'] === 4) && $list = $entity->{"get".ucfirst($fieldName)}()){
          foreach($list as $k => $obj){
            $keys = $obj->getKeys();
            try{
              if(! in_array($em->getUnitOfWork()->getEntityState($obj), [\Doctrine\ORM\UnitOfWork::STATE_NEW, \Doctrine\ORM\UnitOfWork::STATE_MANAGED])){
                $objFinded  = $event->getEntityManager()->getReference($field['targetEntity'], $keys);
                if($objFinded instanceof AbstractEntity){
                  $objFinded->getKeys();
                  $list[$k] = $objFinded;
                }else
                  $list->removeElement($obj);
              }
            }catch(\Doctrine\ORM\ORMException $e){
              $list->removeElement($obj);
            }catch(\Exception $e){}
          }
        }
      }
    }
  }
}