<?php

namespace Solutio\Service;

use Zend\EventManager\EventManagerAwareInterface,
    Zend\EventManager\EventManagerAwareTrait;

abstract class AbstractService implements EventManagerAwareInterface
{
  use EventManagerAwareTrait;
  
  public function __call($name, $arguments)
  {
    if(method_exists($this, $name)){
      $this->getEventManager()->trigger("before." . $name, $this, $arguments);
      $result = call_user_func_array(array(&$this, $name), $arguments);
      array_push($arguments, $result);
      $this->getEventManager()->trigger("after." . $name, $this, $arguments);
      return $result;
    }
  }
}