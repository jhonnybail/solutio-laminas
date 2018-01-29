<?php

namespace Solutio\Service;

use Doctrine\Utils\Data\ArrayObject;

class TriggerMethodServiceDecorator extends AbstractService
{
  private $triggerService;
  
  public function __construct(AbstractService $service)
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
}