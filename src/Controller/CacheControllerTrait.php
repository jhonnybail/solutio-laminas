<?php

namespace Solutio\Controller;

use Zend\Cache\Storage\StorageInterface;

trait CacheControllerTrait
{
  private $cacheable    = false;
  private $cacheAdapter;
  private $ttl          = 0;
  
  public function setCacheable(bool $cacheable) : CacheControllerInterface
  {
    $this->cacheable = $cacheable;
    return $this;
  }
  
  public function isCacheable() : bool 
  {
    return $this->cacheable;
  }
  
  public function setCacheAdapter(StorageInterface $adapter) : CacheControllerInterface
  {
    $this->cacheAdapter = $adapter;
    return $this;
  }
  
  public function getCacheAdapter()
  {
    return $this->cacheAdapter;
  }
  
  public function setLifetime(int $ttl) : CacheControllerInterface
  {
    $this->ttl = $ttl;
    return $this;
  }
  
  public function getLifetime() : int
  {
    return $this->ttl;
  }
}