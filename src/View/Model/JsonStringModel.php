<?php

namespace Solutio\View\Model;

use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\View\Model\ViewModel;

class JsonStringModel extends ViewModel
{
  protected $captureTo = null;

  protected $jsonpCallback = null;

  protected $terminate = true;

  public function setJsonpCallback($callback)
  {
    $this->jsonpCallback = $callback;
    return $this;
  }

  public function serialize()
  {
    $variables = $this->getVariables();
    if ($variables instanceof Traversable) {
      $variables = ArrayUtils::iteratorToArray($variables);
    }

    $options = [
      'prettyPrint' => $this->getOption('prettyPrint'),
    ];

    if (null !== $this->jsonpCallback) {
      return $this->jsonpCallback.'('.$variables[0].');';
    }
    return $variables[0];
  }
}
