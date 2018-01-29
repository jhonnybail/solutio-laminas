<?php

namespace Solutio\Doctrine\Service;

use Solutio\EntityInterface;
use Solutio\EntityRepositoryInterface;

class EntityService extends \Solutio\Service\EntityService
{
  protected $repository;
  
  public function __construct(EntityRepositoryInterface $repository)
  {
    $this->repository = $repository;
  }
  
  public function getRepository() : EntityRepositoryInterface
  {
    return $this->repository;
  }
  
  public function getClassName()
  {
    return $this->getRepository()->getClassName();
  }
  
  public function insert(EntityInterface $entity) : EntityInterface
  {
    return $this->getRepository()->insert($entity);
  }
  
  public function update(EntityInterface $entity) : EntityInterface
  {
    return $this->getRepository()->update($entity);
  }
  
  public function delete(EntityInterface $entity) : EntityInterface
  {
    return $this->getRepository()->delete($entity);
  }
  
  public function getById($id)
  {
    try{
      return $this->getRepository()->findById($id);
    }catch(\Exception $e){
      if(!is_array($id)){
        $className  = $this->getClassName();
        $results    = $this->getRepository()->getCollection(new $className($id), [], [], [], EntityRepositoryInterface::RESULT_OBJECT);
        if($results['total'] > 1)
          throw new \Solutio\Exception('More than one reference was returned by the parameters reported.');
        elseif($results['total'] === 0)
          throw $e;
        return isset($results['result'][0]) ? $results['result'][0] : null;
      }
      throw $e;
    }
  }

  public function find(EntityInterface $entity, $filters = [], $params = [], $fields = [], $type = EntityRepositoryInterface::RESULT_ARRAY) : array
  {
    return $this->getRepository()->getCollection($entity, (array) $filters, (array) $params, (array) $fields, $type);
  }
}