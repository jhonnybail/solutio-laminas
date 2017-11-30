<?php

namespace Solutio\Service;

use Zend\EventManager\EventManagerAwareInterface,
    Zend\EventManager\EventManagerInterface,
    Zend\EventManager\EventManager,
    Zend\EventManager\EventManagerAwareTrait,
    Solutio\Utils\Data\ArrayObject;

abstract class AbstractService implements EventManagerAwareInterface
{
  protected $identifiers  = [];
  protected $events;
    
  public function setEventManager(EventManagerInterface $events)
  {
      $className    = get_class($this);
      $identifiers  = [$className];
      while($identifier = get_parent_class($className)){
        $identifiers[]  = $identifier;
        $className      = $identifier;
      }
      $events->setIdentifiers($identifiers);
      $this->events = $events;
      if (method_exists($this, 'attachDefaultListeners')) {
          $this->attachDefaultListeners();
      }
  }
  
  public function getEventManager()
  {
    if (! $this->events instanceof EventManagerInterface) {
      $this->setEventManager(new EventManager());
    }
    return $this->events;
  }
  
  public function __call($name, $arguments)
  {
    if(method_exists($this, $name)){
      $this->getEventManager()->trigger("before.*", $this, $arguments);
      $this->getEventManager()->trigger("before." . $name, $this, $arguments);
      $result = call_user_func_array(array(&$this, $name), $arguments);
      if(is_array($result))
        $result = new ArrayObject($result);
      array_push($arguments, $result);
      $this->getEventManager()->trigger("after." . $name, $this, $arguments);
      $this->getEventManager()->trigger("after.*", $this, $arguments);
      return $result instanceof ArrayObject ? (array) $result : $result;
    }
  }
}