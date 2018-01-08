<?php

namespace Solutio\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Solutio\View\Strategy\JsonArrayStrategy;

class ViewJsonArrayStrategyFactory implements FactoryInterface
{
  public function __invoke(ContainerInterface $container, $name, array $options = null)
  {
    $jsonRenderer = $container->get(\Solutio\View\Renderer\JsonArrayRenderer::class);
    $jsonStrategy = new JsonArrayStrategy($jsonRenderer);
    return $jsonStrategy;
  }
}
