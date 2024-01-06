<?php

namespace Solutio\View\Strategy;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Solutio\View\Model;
use Laminas\View\Renderer\RendererInterface;
use Laminas\View\ViewEvent;

class JsonStringStrategy extends AbstractListenerAggregate
{
  protected $charset = 'utf-8';

  protected $multibyteCharsets = [
    'UTF-16',
    'UTF-32',
  ];

  protected $renderer;

  public function __construct(RendererInterface $renderer)
  {
    $this->renderer = $renderer;
  }

  public function attach(EventManagerInterface $events, $priority = 1)
  {
    $this->listeners[] = $events->attach(ViewEvent::EVENT_RENDERER, [$this, 'selectRenderer'], $priority);
    $this->listeners[] = $events->attach(ViewEvent::EVENT_RESPONSE, [$this, 'injectResponse'], $priority);
  }
  
  function setCharset($charset)
  {
    $this->charset = (string) $charset;
    return $this;
  }

  public function getCharset()
  {
    return $this->charset;
  }

  public function selectRenderer(ViewEvent $e)
  {
    $model = $e->getModel();

    if (! $model instanceof Model\JsonStringModel) {
      // no JsonModel; do nothing
      return;
    }

    // JsonModel found
    return $this->renderer;
  }

  public function injectResponse(ViewEvent $e)
  {
    $renderer = $e->getRenderer();
    if ($renderer !== $this->renderer) {
      // Discovered renderer is not ours; do nothing
      return;
    }

    $result   = $e->getResult();

    // Populate response
    $response = $e->getResponse();
    $response->setContent($result);
    $headers = $response->getHeaders();

    if ($this->renderer->hasJsonpCallback()) {
      $contentType = 'application/javascript';
    } else {
      $contentType = 'application/json';
    }

    $contentType .= '; charset=' . $this->charset;
    $headers->addHeaderLine('content-type', $contentType);

    if (in_array(strtoupper($this->charset), $this->multibyteCharsets)) {
      $headers->addHeaderLine('content-transfer-encoding', 'BINARY');
    }
  }
}
