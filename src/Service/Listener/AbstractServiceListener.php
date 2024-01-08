<?php

namespace Solutio\Service\Listener;

use Interop\Container\ContainerInterface;
use Laminas\EventManager\EventManagerInterface,
    Laminas\EventManager\ListenerAggregateInterface;

abstract class AbstractServiceListener implements ListenerAggregateInterface
{
  protected $listeners      = [];
  private   $container;
  private   $eventManager;
  private   $priority;
  
  public function __construct(ContainerInterface $container)
  {
    $this->container  = $container;
  }
  
  public function getContainer() : ContainerInterface
  {
    return $this->container;
  }
  
  public function getEventManager() : EventManagerInterface
  {
    return $this->eventManager;
  }

  public function attach(EventManagerInterface $events, $priority = 1){
    $this->eventManager = $events;
    $this->makeListeners($priority);
  }
  
  abstract public function makeListeners($priority = 1);

  public function detach(EventManagerInterface $events)
  {
    foreach ($this->listeners as $index => $listener) {
      $events->detach($listener);
      unset($this->listeners[$index]);
    }
  }
}