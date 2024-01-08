<?php

namespace Solutio\Utils\Net\Mail;

use AcMailer\Service\MailService;
use AcMailer\Model\Email;
use Laminas\View\Model\ViewModel;

class Mail
{
  private $mailService;
  private $charset  = 'utf-8';
  private $template;
  private $data     = [];
  private Email $message;
  
  public function __construct(MailService $mailService)
  {
    $this->mailService = $mailService;
    $this->message = new Email();
  }
  
  public function addTo(array $emails)
  {
    $this->message->setTo($emails);
  }
  
  public function addFrom(array $emails)
  {
    foreach($emails as $from)
      $this->message->setFrom($from['email']);
      $this->message->setFromName($from['name']);
  }
  
  public function setSubject($subject)
  {
    $this->message->setSubject($subject);
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
      $this->message->setTemplate($this->template);
    }else
      $this->message->setBody($this->template);

    $result = $this->mailService->send($this->message);

    if($result->hasThrowable())
      throw $result->getThrowable();
  }
  
}