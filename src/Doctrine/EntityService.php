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

  public function getEntity($data = null)
  {
    return $this->entity;
  }

  public function getById($id)
  {
    $repo   = $this->em->getRepository($this->entity);
    try{
      return $repo->find($id);
    }catch(\Doctrine\ORM\ORMInvalidArgumentException $e){
      if(!is_array($id)){
        $results = $repo->findById($id);
        if(count($results) > 1)
          throw new \Solutio\Exception('More than one reference was returned by the parameters reported.');
        return isset($results[0]) ? $results[0] : null;
      }
      throw $e;
    }
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
      $values[$id]  = null;
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
      if(count($values) > 1 && isset($data['id']) && !empty($data['id'])){
        $results    = $this->em->getRepository(get_class($entity))->findById($data['id']);
        if(count($results) > 1)
          throw new \Solutio\Exception('More than one reference was returned by the parameters reported.');
        if(count($results) === 0)
          throw new \Solutio\Exception('The entity don\'t extists.');
        $newEntity  = $results[0];
      }else {
        $newEntity = $this->em->getReference(get_class($entity), $values);
        if($newEntity === null)
          $newEntity = $entity;
        $newEntity->fromArray([]);
      }

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
    
    foreach($data as $k => $v)
      if($v === '')
        $data[$k] = null;

    $newEntity->fromArray($data);
    return $newEntity;
  }
}