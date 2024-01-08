<?php

namespace Solutio\Log;

use Laminas\Log\Writer\Stream;
use Laminas\Log\Logger;

abstract class AbstractLogger extends Logger
{
  private $_logDir;
  private $_logFile;
  
  public function __construct($logFile, $logDir = null)
  {
    parent::__construct();

    if (null == $logDir) {
      $logDir = sys_get_temp_dir();
    }
    $this->setLogDir($logDir);
    $this->setLogFile($logFile);

    $writer = new Stream($logDir . DIRECTORY_SEPARATOR . $logFile);
    $this->addWriter($writer);
  }
  
  public function getLogDir()
  {
    return $this->_logDir;
  }
  
  public function setLogDir($logDir)
  {
    $logDir = trim($logDir);
    if (!file_exists($logDir) || !is_writable($logDir)) {
      throw new \Solutio\InvalidArgumentException("Diretório inválido!");
    }

    $this->_logDir = $logDir;
  }
  
  public function getLogFile()
  {
    return $this->_logFile;
  }
  
  public function setLogFile($logFile)
  {
    $logFile = trim($logFile);
    if (null === $logFile || '' == $logFile) {
      throw new \Solutio\InvalidArgumentException("Arquivo inválido!");
    }
    $this->_logFile = $logFile;
  }
}