<?php

namespace Solutio\Doctrine;

use Solutio\Log\AbstractLogger;
use Doctrine\DBAL\Logging\SQLLogger as LogInterface;

class SqlLogger extends AbstractLogger implements LogInterface
{
  public function startQuery ($sql, array $params = null, array $types = null)
  {
    $msg = 'SQL: ' . $sql;
    if ($params) {
      $msg .= PHP_EOL . "\tPARAMS: " . json_encode($params);
    }
    if ($types) {
      $msg .= PHP_EOL . "\tTIPOS: " . json_encode($types);
    }
    $this->debug($msg);
  }
  
  public function stopQuery ()
  {}
}