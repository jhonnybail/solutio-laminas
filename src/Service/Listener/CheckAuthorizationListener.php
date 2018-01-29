<?php

namespace Solutio\Service\Listener;

use Zend\EventManager\EventInterface,
    Solutio\Utils\Data\StringManipulator,
    Solutio\Exception;

class CheckAuthorizationListener extends AbstractServiceListener
{
  public function makeListeners($priority = 1)
  {
    $this->listeners[] = $this->getEventManager()->getSharedManager()->attach(\Solutio\Service\EntityService::class, 'before.*', [$this, 'checkAllowMethods'], 50);
  }
  
  public function checkAllowMethods(EventInterface $e)
  {
    $application  = $this->getContainer()->get('application');
    $controller   = $application->getMvcEvent()->getTarget();
    $request      = $application->getRequest();
    $response     = $application->getResponse();
    $method       = $request->getMethod();
    if($controller instanceof \Solutio\Controller\ServiceRestController)
      if (!in_array($method, $controller->getAllowedCollectionMethods()) && !in_array('*', $controller->getAllowedCollectionMethods())) {
        $response->setStatusCode(
          \Zend\Http\PhpEnvironment\Response::STATUS_CODE_405
        );
        throw new Exception('Method Not Allowed');
      }
  }
}