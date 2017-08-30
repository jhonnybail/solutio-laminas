<?php

namespace Solutio\Utils\Net;

use AcMailer\Service\MailService;
use Zend\View\Model\ViewModel;

class Message
{
  private $mailService;
  private $charset = 'utf-8';
  
  public function __construct(MailService $mailService)
  {
    $this->mailService = $mailService;  
  }
  
  public function addTo(array $emails)
  {
    $this->mailService->getMessage()->addTo($emails);
  }
  
  public function addFrom(array $emails)
  {
    foreach($emails as $from)
      $this->mailService->getMessage()->addFrom($from['email'], $from['name']);
  }
  
  public function setSubject($subject)
  {
    $this->mailService->setSubject($subject);
  }
  
  public function setCharset($charset)
  {
    $this->charset = $charset;
  }
  
  public function addBody(ViewModel $template)
  {
    $this->mailService->setTemplate($template, [
        'charset' => $this->charset
      ]);
  }
  
  public function send()
  {
    $this->mailService->send();
  }
  
}