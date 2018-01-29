<?php

namespace Solutio\View\Renderer;

use JsonSerializable;
use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\View\Exception;
use Solutio\View\Model\JsonStringModel;
use Zend\View\Model\ModelInterface as Model;
use Zend\View\Renderer\JsonRenderer;
use Zend\View\Renderer\RendererInterface as Renderer;
use Zend\View\Resolver\ResolverInterface as Resolver;

class JsonStringRenderer extends JsonRenderer
{
  public function render($nameOrModel, $values = null)
  {
    if ($nameOrModel instanceof Model) {
      if ($nameOrModel instanceof JsonStringModel) {
        $children = $this->recurseModel($nameOrModel, false);
        $this->injectChildren($nameOrModel, $children);
        $values = $nameOrModel->serialize();
      } else {
        $values = $this->recurseModel($nameOrModel);
        $values = $values;
      }

      if ($this->hasJsonpCallback()) {
        $values = $this->jsonpCallback . '(' . $values . ');';
      }
      return $values;
    }

    if (null === $values) {
      if (! is_object($nameOrModel) || $nameOrModel instanceof JsonSerializable) {
        $return = Json::encode($nameOrModel);
      } elseif ($nameOrModel instanceof Traversable) {
        $nameOrModel = ArrayUtils::iteratorToArray($nameOrModel);
        $return = Json::encode($nameOrModel);
      } else {
        $return = Json::encode(get_object_vars($nameOrModel));
      }

      if ($this->hasJsonpCallback()) {
        $return = $this->jsonpCallback . '(' . $return . ');';
      }
      return $return;
    }

    // use case 3: Both $nameOrModel and $values are populated
    throw new Exception\DomainException(sprintf(
      '%s: Do not know how to handle operation when both $nameOrModel and $values are populated',
      __METHOD__
    ));
  }
}
