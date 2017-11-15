<?php

namespace Solutio\Doctrine\Listeners;

use Doctrine\ORM\Mapping as ORM;
use Solutio\Doctrine\AbstractEntity;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Ramsey\Uuid\Uuid;

class GenerateIdentifierListener
{
  private $listMaxCount         = [];
  private $allowGeneratorFlush  = [];
  private $generatorFieldName   = [];
  
  /** 
   * @ORM\PrePersist
   */
  public function prePersistHandler(AbstractEntity $entity, LifecycleEventArgs $event)
  {
    $keys   = $entity->getKeys();
    if(!isset($this->generatorFieldName[get_class($entity)])){
      //Verifica algum id vazio e gera automaticamente o valor
      $field  = '';
      $cont   = 0;
      foreach($keys as $key => $value)
        if(empty($value)){
          $field = $key;
          $cont++;
        }
      //
      
      if(!empty($field)){
        if($cont > 1){
          $reflection = \Zend\Server\Reflection::reflectClass($entity);
          $field      = '';
          foreach($reflection->getProperties() as $property){
              $propertyAnnotations = (new \Doctrine\Common\Annotations\AnnotationReader)->getPropertyAnnotations($property);
              foreach($propertyAnnotations as $propertyAnnotation){
                  if($propertyAnnotation instanceof ORM\GeneratedValue && $propertyAnnotation->strategy === "NONE"){
                      $field  = $property->getName();
                      break;
                  }
              }
          }
        }
      }
      
      $this->generatorFieldName[get_class($entity)] = $field;
    }else{
      $field  = $this->generatorFieldName[get_class($entity)];
      $cont   = 1;
    }
      
    if(!empty($field)){
      
      unset($keys[$field]);
      $listIndex = get_class($entity) . (count($keys) === 0 ? '': new \Solutio\Utils\Data\ArrayObject($keys));
      if(!isset($this->listMaxCount[$listIndex])){
        $query  = $event->getEntityManager()
                      ->createQueryBuilder()
                      ->from(get_class($entity), 'e')
                      ->select("MAX(e.{$field})");
        foreach($keys as $k => $v)
          $query->andWhere("e.{$k} = :{$k}")
                  ->setParameter($k, $v);
        $id     = $query->getQuery()
                      ->getSingleScalarResult();
      }else
        $id = $this->listMaxCount[$listIndex];
      
      $entity->{"set".ucfirst($field)}(++$id);
      if($cont === 1)
        $this->listMaxCount[$listIndex] = $id;
    
    }
    
    //Verifica se o attributo Id existir e estiver vazio, gera hash automaticamente
    if(method_exists($entity, 'getId') && empty($entity->getId())){
      $entity->setId((string) Uuid::uuid4());
    }
  }
  
  /** 
   * @ORM\PreFlush
   */
  public function preFlushHandler(AbstractEntity $entity, PreFlushEventArgs $arguments)
  {
    $keys   = $entity->getKeys();
    $field  = $this->generatorFieldName[get_class($entity)];
    unset($keys[$field]);
    $listIndex = get_class($entity) . (count($keys) === 0 ? '': new \Solutio\Utils\Data\ArrayObject($keys));
    if($field && !isset($this->listMaxCount[$listIndex])){
      $query  = $arguments->getEntityManager()
                    ->createQueryBuilder()
                    ->from(get_class($entity), 'e')
                    ->select("MAX(e.{$field})");
      foreach($keys as $k => $v)
        $query->andWhere("e.{$k} = :{$k}")
                ->setParameter($k, $v);
      $id     = $query->getQuery()
                    ->getSingleScalarResult();
      $entity->{"set".ucfirst($field)}(++$id);
      $this->listMaxCount[$listIndex]         = $id;
      $this->allowGeneratorFlush[$listIndex]  = true;
    }elseif(isset($this->allowGeneratorFlush[$listIndex]))
      $entity->{"set".ucfirst($field)}(++$this->listMaxCount[$listIndex]);
  }
}