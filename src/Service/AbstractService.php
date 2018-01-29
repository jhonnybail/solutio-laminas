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
}