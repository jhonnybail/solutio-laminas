<?php

namespace Solutio\Doctrine;

use Zend\Hydrator;

class EntityService
{
  private $entity;
  private $em;
  
  public function __construct(\Doctrine\ORM\EntityManager $em, \Solutio\AbstractEntity $entity = null)
  {
    $this->em     = $em;
    if($entity)
      $this->setEntity($entity);
  }
  
  public function getEntityManager()
  {
    return $this->em;
  }
  
  public function setEntity(\Solutio\AbstractEntity $entity)
  {
    $this->entity = get_class($entity);
    return $this;
  }
  
  public function getEntity()
  {
    return $this->entity;
  }
  
  public function getById($id)
  {
    $repo   = $this->em->getRepository($this->entity);
    return $repo->find($id);
  }
  
  public function find(\Solutio\AbstractEntity $entity, $params, $fields, $type = EntityRepository::RESULT_ARRAY)
  {
    $repo 	= $this->em->getRepository($this->entity);
    return $repo->getCollection($entity, $params, $fields, $type);
  }
  
  public function save(\Solutio\AbstractEntity $entity)
  {
    $entity = $this->getReferenceByEntity($entity);
    $this->em->persist($entity);
    $this->em->flush();
    return $entity;
  }
  
  public function insert(\Solutio\AbstractEntity $entity)
  {
    return $this->save($entity);
  }

  public function update(\Solutio\AbstractEntity $entity)
  {
    return $this->save($entity);
  }

  public function delete(\Solutio\AbstractEntity $entity)
  {
    $entity = $this->getReferenceByEntity($entity);
    $this->em->remove($entity);
    $this->em->flush();
    return $entity;
  }
  
  protected function getReferenceByEntity(\Solutio\AbstractEntity $entity, $onlyReference = false)
  {
    $values = [];
    $data = $entity->toArray();
    $meta = $this->getEntityManager()->getMetadataFactory()->getMetadataFor(get_class($entity));
    $ids  = $meta->identifier;
    foreach($ids as $id){
      if(!empty($data[$id])){
        if($data[$id] instanceof \Solutio\AbstractEntity){
          $metaField = $this->getEntityManager()->getMetadataFactory()->getMetadataFor(get_class($entity));
          $map = $metaField->getAssociationMapping($id);
          $values[$id] = $data[$id]->toArray()[$map['joinColumns'][0]['referencedColumnName']];
        }else
          $values[$id] = $data[$id];
      }
    }
    try{
      $newEntity = $this->em->getReference(get_class($entity), $values);
      if($newEntity === null)
        $newEntity = $entity;
      if($onlyReference)
        return $newEntity;
    }catch(\Doctrine\ORM\ORMException $e){
      $newEntity = $entity;
    }
    
    $maps 	= $meta->getAssociationMappings();
    foreach($data as $k => $v){
      if(isset($maps[$k]) && $v !== null){
        $am = $meta->getAssociationMapping($k);
        if(($am['type'] == 1 || $am['type'] == 2) && $am['isCascadePersist']){
          $data[$k] = $this->getReferenceByEntity($v);
        }elseif($am['type'] == 1 || $am['type'] == 2){
          $data[$k] = $this->getReferenceByEntity($v, true);
        }
      }
    }
      
    $newEntity->fromArray($data);
    return $newEntity;
  }
}