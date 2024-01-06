<?php

namespace Solutio\Utils\Net\Mail;

use AcMailer\Service\MailService;
use Laminas\View\Model\ViewModel;

class Mail
{
  private $mailService;
  private $charset  = 'utf-8';
  private $template;
  private $data     = [];
  
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
  
  public function setTemplate($template)
  {
    $this->template = $template;
  }
  
  public function addData(array $data)
  {
    $this->data = array_merge($this->data, $data);
  }
  
  public function send()
  {
    if($this->template instanceof ViewModel){
      $applyData = function($template, $data, $function){
        $template->setVariables($data);
        if($template->hasChildren()){
          foreach($template->getChildren() as $child)
            $function($child, $data, $function);
        }
      };
      $applyData($this->template, $this->data, $applyData);
      $this->mailService->setTemplate($this->template);
    }else
      $this->mailService->setBody($this->template);
    $result = $this->mailService->send();
    if(!empty($result->getException()))
      throw $result->getException();
  }
  
}