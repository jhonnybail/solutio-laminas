<?php

namespace Solutio\Doctrine\Service\Listener;

use Zend\EventManager\EventInterface;

class CreateTransactionListener extends \Solutio\Service\Listener\AbstractServiceListener
{
  public function makeListeners($priority = 1)
  {
    $this->listeners[] = $this->getEventManager()->getSharedManager()->attach(\Solutio\Doctrine\Service\EntityService::class, 'before.*', [$this, 'beginTransaction'], 10);
    $this->listeners[] = $this->getEventManager()->getSharedManager()->attach(\Solutio\Doctrine\Service\EntityService::class, 'after.*',  [$this, 'commit'], 10);
    $this->listeners[] = $this->getEventManager()->getSharedManager()->attach('*', 'dispatch.error', [$this, 'rollback'], 10);
  }
  
  public function beginTransaction(EventInterface $event)
  {
    if(!$this->getContainer()->get('Doctrine\ORM\EntityManager')->getConnection()->isTransactionActive())
      $this->getContainer()->get('Doctrine\ORM\EntityManager')->beginTransaction();
  }
  
  public function commit(EventInterface $event)
  {
    if($this->getContainer()->get('Doctrine\ORM\EntityManager')->getConnection()->isTransactionActive())
      $this->getContainer()->get('Doctrine\ORM\EntityManager')->commit();
  }
  
  public function rollback(EventInterface $event)
  {
    if($this->getContainer()->get('Doctrine\ORM\EntityManager')->getConnection()->isTransactionActive())
      $this->getContainer()->get('Doctrine\ORM\EntityManager')->rollback();
  }
}