<?php

namespace Solutio\Doctrine\Service;

use Solutio\Utils\Data\ArrayObject;
use Solutio\EntityInterface;
use Solutio\EntityRepositoryInterface;

class TriggerMethodServiceDecorator extends EntityService
{
  private $triggerService;
  
  public function __construct(EntityService $service)
  {
    $this->triggerService = $service;
  }
  
  public function __call($name, $arguments)
  {
    if(method_exists($this->triggerService, $name)){
      $this->getEventManager()->trigger("before.*", $this->triggerService, $arguments);
      $this->getEventManager()->trigger("before." . $name, $this->triggerService, $arguments);
      $result = call_user_func_array(array(&$this->triggerService, $name), $arguments);
      if(is_array($result))
        $result = new ArrayObject($result);
      array_push($arguments, $result);
      $this->getEventManager()->trigger("after." . $name, $this->triggerService, $arguments);
      $this->getEventManager()->trigger("after.*", $this->triggerService, $arguments);
      return $result instanceof ArrayObject ? (array) $result : $result;
    }
  }
  
  public function getRepository() : EntityRepositoryInterface
  {
    return $this->triggerService->getRepository();
  }
  
  public function getClassName()
  {
    return $this->getRepository()->getClassName();
  }
  
  public function insert(EntityInterface $entity) : EntityInterface
  {
    return $this->__call('insert', [$entity]);
  }
  
  public function update(EntityInterface $entity) : EntityInterface
  {
    return $this->__call('update', [$entity]);
  }
  
  public function delete(EntityInterface $entity) : EntityInterface
  {
    return $this->__call('delete', [$entity]);
  }
  
  public function getById($id)
  {
    return $this->__call('getById', [$id]);
  }

  public function find(EntityInterface $entity, $filters = [], $params = [], $fields = [], $type = EntityRepositoryInterface::RESULT_ARRAY) : array
  {
    return $this->__call('find', [$entity, $filters, $params, $fields, $type]);
  }
}