<?php

namespace Solutio\Service\Listener;

use Zend\EventManager\EventInterface;

class CreateTransactionListener extends AbstractServiceListener
{
  public function makeListeners($priority = 1)
  {
    $this->listeners[] = $this->getEventManager()->getSharedManager()->attach(\Solutio\Service\EntityService::class, 'before.insert', [$this, 'beginTransaction'], 100);
    $this->listeners[] = $this->getEventManager()->getSharedManager()->attach(\Solutio\Service\EntityService::class, 'before.update', [$this, 'beginTransaction'], 100);
    $this->listeners[] = $this->getEventManager()->getSharedManager()->attach(\Solutio\Service\EntityService::class, 'before.delete', [$this, 'beginTransaction'], 100);
    
    $this->listeners[] = $this->getEventManager()->getSharedManager()->attach(\Solutio\Service\EntityService::class, 'after.insert',  [$this, 'commit'], 100);
    $this->listeners[] = $this->getEventManager()->getSharedManager()->attach(\Solutio\Service\EntityService::class, 'after.update',  [$this, 'commit'], 100);
    $this->listeners[] = $this->getEventManager()->getSharedManager()->attach(\Solutio\Service\EntityService::class, 'after.delete',  [$this, 'commit'], 100);
    
    $this->listeners[] = $this->getEventManager()->getSharedManager()->attach('*', 'dispatch.error', [$this, 'rollback'], 100);
  }
  
  public function beginTransaction(EventInterface $event)
  {
    $this->getContainer()->get('Doctrine\ORM\EntityManager')->beginTransaction();
  }
  
  public function commit(EventInterface $event)
  {
    $this->getContainer()->get('Doctrine\ORM\EntityManager')->commit();
  }
  
  public function rollback(EventInterface $event)
  {
    if($this->getContainer()->get('Doctrine\ORM\EntityManager')->getConnection()->isTransactionActive())
      $this->getContainer()->get('Doctrine\ORM\EntityManager')->rollback();
  }
}