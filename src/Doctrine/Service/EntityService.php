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
  
  protected function insert(EntityInterface $entity) : EntityInterface
  {
    return $this->getRepository()->insert($entity);
  }
  
  protected function update(EntityInterface $entity) : EntityInterface
  {
    return $this->getRepository()->update($entity);
  }
  
  protected function delete(EntityInterface $entity) : EntityInterface
  {
    return $this->getRepository()->delete($entity);
  }
  
  protected function getById($id) : EntityInterface
  {
    try{
      return $this->getRepository()->find($id);
    }catch(\Exception $e){
      if(!is_array($id)){
        $results = $this->getRepository()->getCollection($id);
        if(count($results) > 1)
          throw new \Solutio\Exception('More than one reference was returned by the parameters reported.');
        return isset($results[0]) ? $results[0] : null;
      }
      throw $e;
    }
  }

  protected function find(EntityInterface $entity, $filters = [], $params = [], $fields = [], $type = EntityRepositoryInterface::RESULT_ARRAY) : array
  {
    return $this->getRepository()->getCollection($entity, (array) $filters, (array) $params, (array) $fields, $type);
  }
}