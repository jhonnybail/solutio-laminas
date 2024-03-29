<?php

namespace Solutio\Controller;

use Laminas\Cache\Storage\StorageInterface,
    Solutio\Service\EntityService;

interface CacheControllerInterface
{
  public function setCacheable(bool $cacheable) : CacheControllerInterface;
  
  public function isCacheable() : bool ;
  
  public function setCacheAdapter(StorageInterface $adapter) : CacheControllerInterface;
  
  public function getCacheAdapter();
  
  public function setLifetime(int $ttl) : CacheControllerInterface;
  
  public function getLifetime() : int;

  public function getService() : EntityService;
}