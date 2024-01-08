<?php

/**
 * Solutio.Me
 *
 * @package     Solutio
 * @link        http://github.com/jhonnybail/solutio-laminas
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio;

interface EntityInterface extends \JsonSerializable
{
  public function fromArray(array $data);

  public function toArray() : array;
  
  public function getChangedValues() : array;
  
  public function getChildrenPendingRemovation() : array;

  public function getKeys() : array;

  public static function NameOfPrimaryKeys() : array;
}