<?php

namespace Solutio;

use Zend\Mvc\MvcEvent,
    Zend\EventManager\Event,
    Zend\View\Model\JsonModel,
    Doctrine\DBAL\Logging\LoggerChain,
    Solutio\Doctrine\SqlLogger,
    Solutio\Utils\Data\ArrayObject,
    Solutio\Utils\Data\StringManipulator,
    Solutio\Utils\Data\DateTime;

class Module
{
  const VERSION = '2.5.23';
  
  public function onBootstrap(MvcEvent $e)
  {
    $sys                = new ArrayObject;
    $sys['directory']		= $_SERVER['DOCUMENT_ROOT'];
    if(strlen($sys['directory']) > 0 && $sys['directory'][strlen($sys['directory'])-1] != "/")
      $sys['directory'] .= '/';
    
    if(isset($_SERVER['SERVER_PROTOCOL'])){
      $protocol           = explode('/', $_SERVER['SERVER_PROTOCOL']);
      $sys['protocol']    = strtolower($protocol[0]);
    }
    if(isset($_SERVER['HTTP_HOST']))
     $sys['url']         = $sys['protocol']."://".$_SERVER['HTTP_HOST']."/";

    System::SetSystem((array) $sys);
    
    $e->getTarget()->getEventManager()->getSharedManager()
                                        ->attach(\Zend\Mvc\Controller\AbstractController::class,
                                                'dispatch', [$this, 'loadRequestCache'], 201);
    $e->getTarget()->getEventManager()->getSharedManager()
                                        ->attach(\Zend\Mvc\Controller\AbstractController::class,
                                                'dispatch', [$this, 'beginTransaction'], 201);
    $e->getTarget()->getEventManager()->getSharedManager()
                                        ->attach(\Zend\Mvc\Controller\AbstractController::class,
                                                'dispatch', [$this, 'saveRequestCache'], 0);
    $e->getTarget()->getEventManager()->attach('finish',          [$this, 'commitTransaction'], 0);
    $e->getTarget()->getEventManager()->attach('dispatch.error',  [$this, 'onDispatchError'], 0);
    $e->getTarget()->getEventManager()->attach('dispatch.error',  [$this, 'rollbackTransaction'], 1);
    
    if(!($e->getRequest() instanceof \Zend\Console\Request))
      $e->getTarget()->getEventManager()->attach('finish',          [$this, 'applyCors'], 0);
    
    //Register Listeners Aggregate
    $serviceListeners = $e->getTarget()->getConfig()['service_listener'];
    foreach($serviceListeners as $serviceListener => $invokable){
      (new $invokable)($e->getTarget()->getServiceManager(), $serviceListener);
    }
    //
    
    //Register Doctrine Log
    $config = $e->getApplication()->getConfig()['solutio']['logs']['doctrine'];
    if($config['active']){
      $sm     = $e->getApplication()->getServiceManager();
      $em     = $sm->get('Doctrine\ORM\EntityManager');
      try{
        $log    = new SqlLogger((new \DateTime)->format("YmdHis") . ".log", $config['path']);
        if (null !== $em->getConfiguration()->getSQLLogger()) {
            $logger = new LoggerChain();
            $logger->addLogger($log);
            $logger->addLogger($em->getConfiguration()->getSQLLogger());
            $em->getConfiguration()->setSQLLogger($logger);
        } else {
            $em->getConfiguration()->setSQLLogger($log);
        }
      }catch(\Exception $e){}
    }
    //
    
    //load class Log
    $stream = new \Zend\Log\Writer\Stream('php://output');
    $logger = new \Zend\Log\Logger;
    $logger->addWriter($stream);
    //
    
    //Error PHP dispatch Exception
    $module = $this;
    ini_set('display_errors', false);
    register_shutdown_function(function() use ($module, $e){
      $error = error_get_last();
      if(!is_null($error) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])){
        ob_clean();
        $number = gc_collect_cycles();
        $exception = new \ErrorException($error['message'], null, isset($error['code']) ? $error['code'] : null, $error['file'], $error['line']);
        $data       = $module->getDataException($e, $exception);
        $data['success']  = false;
        
        header('Content-Type: application/json; charset=utf-8');
        header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . " {$data['status']}");
        echo json_encode($data);
        exit;
      }
    });
  }

  public function getConfig()
  {
    return include __DIR__ . '/../config/module.config.php';
  }
  
  public function loadRequestCache($e)
  {
    if($e->getRequest() instanceof \Zend\Console\Request){
      $path = json_encode($e->getRequest()->getContent());
    }else{
      $path = $e->getRequest()->getUri()->getPath() . ($e->getRequest()->getQuery()->count() > 0 ? urlencode(json_encode($e->getRequest()->getQuery()->toArray())) : '');
    }
    $key        = $path;
    $controller = $e->getTarget();
    if($controller instanceof Controller\CacheControllerInterface){
      $adapter    = $controller->getCacheAdapter();
      if($controller->isCacheable() && $adapter && $e->getRequest()->getMethod() === 'GET'){
        $content  = $adapter->getItem($key);
        if($content !== null){
          $model = new View\Model\JsonStringModel([$content]);
          $e->setViewModel($model);
          $e->stopPropagation();
          return $model;
        }
      }
    }
  }
  
  public function beginTransaction($e)
  {
    $em = $e->getApplication()->getServiceManager()->get(\Doctrine\ORM\EntityManager::class);
    if(!$em->getConnection()->isTransactionActive())
      $em->beginTransaction();
  }
  
  public function commitTransaction($e)
  {
    $em = $e->getApplication()->getServiceManager()->get(\Doctrine\ORM\EntityManager::class);
    if($em->getConnection()->isTransactionActive())
      $em->commit();
  }
  
  public function rollbackTransaction($e)
  {
    $em = $e->getApplication()->getServiceManager()->get(\Doctrine\ORM\EntityManager::class);
    if($em->getConnection()->isTransactionActive()){
      $em->rollback();
      if(!empty($em->getCache()))
        $em->getCache()->evictEntityRegions();
    }
  }
  
  public function saveRequestCache($e)
  {
    $request    = $e->getRequest();
    $controller = $e->getTarget();
    if($controller instanceof Controller\CacheControllerInterface){
      if($controller->isCacheable()){
        $adapter  = $controller->getCacheAdapter();
        $reposito = $controller->getService()->getClassname();
        if($e->getRequest() instanceof \Zend\Console\Request){
          $path = json_encode($e->getRequest()->getContent());
        }else{
          $path = $e->getRequest()->getUri()->getPath() . ($e->getRequest()->getQuery()->count() > 0 ? urlencode(json_encode($e->getRequest()->getQuery()->toArray())) : '');
        }
        $key      = $path;
        $listaCa  = [];
        if($adapter->hasItem($reposito))
          $listaCa = $adapter->getItem($reposito);
        if($adapter && $request->getMethod() === 'GET'){
          $sharedEvents = $e->getApplication()->getEventManager()->getSharedManager();
          $sharedEvents->attach(\Zend\View\View::class, 'response', function($e) use ($adapter, $listaCa, $key, $reposito){
            $content  = $e->getResult();
            $adapter->addItem($key, $content);
            $listaCa[$key] = true;
            if(!$adapter->hasItem($reposito))
              $adapter->addItem($reposito, $listaCa);
            else
              $adapter->replaceItem($reposito, $listaCa);
          });
        }elseif($adapter && $e->getRequest()->getMethod() !== 'GET'
                  && $controller->getService() instanceof Service\EntityService){
          if(count($listaCa) > 0){
            foreach($listaCa as $k => $v)
              $adapter->removeItem($k);
          }
        }
      }
    }
  }
  
  private function getDataException(Event $e, \Throwable $exception, $log = true) : array
  {
    $logConf          = $e->getApplication()->getServiceManager()->get('config')['solutio']['logs']['system'];
    $errorConf        = $e->getApplication()->getServiceManager()->get('config')['solutio']['errors'];
    $data             = [];
    $data['message']  = $exception->getMessage();
    if(!$errorConf['hidden'] && !$errorConf['hidden_trace'])
      $data['trace'] = $exception->getTrace();
    
    //Get remainder memory
    $memUsage   = memory_get_usage(true);
    $memLimit   = preg_replace("![^0-9]!", "", ini_get("memory_limit")) * 1024 * 1024;
    $textLimit = ceil($memLimit*0.0025*0.1);
    //
      
    if($e->getRequest() instanceof \Zend\Console\Request){
      $path = json_encode($e->getRequest()->getContent());
    }else{
      $path = $e->getRequest()->getUri()->getPath();
    }
      
    $e->getResponse()->setStatusCode(\Zend\Http\PhpEnvironment\Response::STATUS_CODE_400);
    if($exception instanceof \Zend\Json\Exception\RuntimeException){
      if($logConf['active'] && $log){
        try{
          $writer = new \Zend\Log\Writer\Stream($logConf['path'] . 'EXRUT-' . (new DateTime)->format("YmdHis") . '-' . rand(1, 100) . '.log');
          $logger = new \Zend\Log\Logger();
          $logger->addWriter($writer);
          $logger->info('--- Initial Run Time Exception ---');
          $logger->info("URI: {$path}");
          $logger->info("Content: {$e->getTarget()->getRequest()->getContent()}");
          $logger->err("Message: {$exception->getMessage()}");
          $logger->err("Trace: {$exception->getTraceAsString()}");
          $logger->info('--- End Run Time Exception ---');
        }catch(\Exception $e){}
      }
      if($errorConf['hidden'])
        $data['message'] = 'Invalid Request JSON';
    }elseif(StringManipulator::GetInstance(get_class($exception))->search('Doctrine')){
      if($logConf['active'] && $log){
        try{
          $writer = new \Zend\Log\Writer\Stream($logConf['path'] . 'EXSQL-' . (new DateTime)->format("YmdHis") . '-' . rand(1, 100) . '.log');
          $logger = new \Zend\Log\Logger();
          $logger->addWriter($writer);
          $logger->info('--- Initial DB Exception ---');
          $logger->info("URI: {$path}");
          $logger->info("Content: {$e->getTarget()->getRequest()->getContent()}");
          $logger->err("Message: {$exception->getMessage()}");
          $logger->err("Trace: {$exception->getTraceAsString()}");
          $logger->info('--- End DB Exception ---');
        }catch(\Exception $e){}
      }
      if($errorConf['hidden'])
        $data['message'] = 'Error processing information on server. Our support team has already been notified.';
    }elseif($exception instanceof \Solutio\NotFoundException)
      $e->getResponse()->setStatusCode(
        \Zend\Http\PhpEnvironment\Response::STATUS_CODE_404,
        'Not Found'
      );
    elseif($exception instanceof \InvalidArgumentException)
      $e->getResponse()->setStatusCode(
        \Zend\Http\PhpEnvironment\Response::STATUS_CODE_409,
        'Conflict'
      );
    else{
      if($logConf['active'] && $log){
        try{
          $writer = new \Zend\Log\Writer\Stream($logConf['path'] . 'EXSER-' . (new DateTime)->format("YmdHis") . '-' . rand(1, 100) . '.log');
          $logger = new \Zend\Log\Logger();
          $logger->addWriter($writer);
          $logger->info('--- Initial Server Exception ---');
          $logger->info("URI: {$path}");
          if(strlen($e->getTarget()->getRequest()->getContent()) <= $textLimit)
            $logger->info("Content: {$e->getTarget()->getRequest()->getContent()}");
          else
            $logger->info("Content: ".substr($e->getTarget()->getRequest()->getContent(), 0, $textLimit) . "...");
          $logger->err("Message: {$exception->getMessage()}");
          $logger->err("Trace: {$exception->getTraceAsString()}");
          $logger->info('--- End Server Exception ---');
        }catch(\Exception $e){}
      }
      if($errorConf['hidden'])
        $data['message'] = 'Someone error running on the server happened. Our support team has already been notified.';
    }
    $data['status']   = $e->getResponse()->getStatusCode();
    return $data;
  }
  
  public function onDispatchError($e) 
  { 
    $error      = $e->getError(); 
    if (!$error || !($e->getTarget() instanceof \Solutio\Controller\ServiceRestController)) { 
      // No error? nothing to do. 
      return; 
    } 

    $request = $e->getRequest(); 
    $headers = $request->getHeaders(); 
    if (!$headers->has('Accept')) { 
      // nothing to do; can't determine what we can accept 
      return; 
    } 

    $accept = $headers->get('Accept'); 
    if (!$accept->match('application/json')) { 
      // nothing to do; does not match JSON 
      return; 
    } 

    $exception  = $e->getParam('exception');
    if(!empty($exception)){
      $data = $this->getDataException($e, $exception);
    }elseif(!empty($e->getParam('controller-class')))
      $data['message']  = $e->getParam('controller-class');
    elseif($error === 'error-router-no-match')
      $data['message']  = 'Route don\'t exists.';
    
    $data['success'] = false;
    
    $model = new JsonModel($data); 
    
    $e->setViewModel($model); 
    $e->stopPropagation(); 
    return $model; 
  }
  
  public function applyCors(MvcEvent $e)
  {
    $cors = $e->getApplication()->getServiceManager()->get('config')['solutio']['cors'];
    
    //Allow-Origin
    if(!$e->getResponse()->getHeaders()->has("Access-Control-Allow-Origin")){
      $origin = System::GetVariable('HTTP_HOST');
      if(in_array('*', $cors['origin']) || in_array($origin, $cors['origin']))
        $e->getResponse()->getHeaders()->addHeaderLine("Access-Control-Allow-Origin", in_array('*', $cors['origin']) ? '*' : $origin);
    }
    
    //Allow-Methods
    if(!$e->getResponse()->getHeaders()->has("Access-Control-Allow-Methods") && count($cors['methods']) > 0){
      $methods = '';
      foreach($cors['methods'] as $method) $methods .= $method . ', ';
      $methods = substr($methods, 0, -2);
      $e->getResponse()->getHeaders()->addHeaderLine("Access-Control-Allow-Methods", $methods);
    }
    
    //Allow-Headers
    if(!$e->getResponse()->getHeaders()->has("Access-Control-Allow-Headers") && count($cors['headers.allow']) > 0){
      $headers = '';
      foreach($cors['headers.allow'] as $header) $headers .= $header . ', ';
      $headers = substr($headers, 0, -2);
      $e->getResponse()->getHeaders()->addHeaderLine("Access-Control-Allow-Headers", $headers);
    }
    
    if(!$e->getResponse()->getHeaders()->has("Access-Control-Allow-Credentials") && $cors['credentials'])
      $e->getResponse()->getHeaders()->addHeaderLine("Access-Control-Allow-Credentials", true);
      
    if(!$e->getResponse()->getHeaders()->has("Access-Control-Max-Age") && is_numeric($cors['cache']))
      $e->getResponse()->getHeaders()->addHeaderLine("Access-Control-Max-Age", $cors['cache']);
  }
  
  public function getServiceConfig()
  {
    return [
      'factories' => [
        'Solutio\Utils\Net\Mail' => function($container) {
          return new Utils\Net\Mail\Mail($container->build('acmailer.mailservice.default'));
        }
      ]
    ];
  }
}