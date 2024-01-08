<?php

namespace Solutio\View\Renderer;

use JsonSerializable;
use Traversable;
use Laminas\Stdlib\ArrayUtils;
use Laminas\View\Exception;
use Solutio\View\Model\JsonStringModel;
use Laminas\View\Model\ModelInterface as Model;
use Laminas\View\Renderer\JsonRenderer;
use Laminas\View\Renderer\RendererInterface as Renderer;
use Laminas\View\Resolver\ResolverInterface as Resolver;

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
      }

      if ($this->hasJsonpCallback()) {
        $values = $this->jsonpCallback . '(' . $values . ');';
      }
      return $values;
    }

    if (null === $values) {
      if (! is_object($nameOrModel) || $nameOrModel instanceof JsonSerializable) {
        $return = json_encode($nameOrModel);
      } elseif ($nameOrModel instanceof Traversable) {
        $nameOrModel = ArrayUtils::iteratorToArray($nameOrModel);
        $return = json_encode($nameOrModel);
      } else {
        $return = json_encode(get_object_vars($nameOrModel));
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
