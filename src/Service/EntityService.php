<?php

namespace Solutio\Service;

use Solutio\AbstractEntity;
use Solutio\Doctrine\EntityRepository;

class EntityService extends AbstractService
{
  protected $repository;
  
  public function __construct(EntityRepository $repository)
  {
    $this->repository = $repository;
  }
  
  public function getRepository() : EntityRepository
  {
    return $this->repository;
  }
  
  public function getClassName()
  {
    return $this->getRepository()->getClassName();
  }
  
  protected function insert(AbstractEntity $entity) : AbstractEntity
  {
    return $this->getRepository()->insert($entity);
  }
  
  protected function update(AbstractEntity $entity) : AbstractEntity
  {
    return $this->getRepository()->update($entity);
  }
  
  protected function delete(AbstractEntity $entity) : AbstractEntity
  {
    return $this->getRepository()->delete($entity);
  }
  
  protected function getById($id)
  {
    try{
      return $this->getRepository()->find($id);
    }catch(\Exception $e){
      if(!is_array($id)){
        $results = $this->getRepository()->findById($id);
        if(count($results) > 1)
          throw new \Solutio\Exception('More than one reference was returned by the parameters reported.');
        return isset($results[0]) ? $results[0] : null;
      }
      throw $e;
    }
  }

  protected function find(AbstractEntity $entity, $filters = [], $params = [], $fields = [], $type = EntityRepository::RESULT_ARRAY)
  {
    return $this->getRepository()->getCollection($entity, $filters, $params, $fields, $type);
  }
}