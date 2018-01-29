<?php

/**
 * Solutio.Me
 *
 * @package     Solutio\Utils\Data
 * @link        http://github.com/jhonnybail/solutio-zf2
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio\Utils\Data;

/**
 * Classe substituta de String.
 */
class StringMask
{
  /**
   * Aplica uma mascara em uma string.
   * Para caracteres especiais, aplique ? antes do carácter.
   *
   * @param  string $mask
   * @param  string $value
   * @return \Solutio\Utils\Data\StringManipulator
   */
  public static function mask($mask, $value, $charMask = 'X')
  {
    $mask   = new StringManipulator($mask);
    $value  = new StringManipulator($value);
    
    if($mask->match("!{$charMask}!")->count() > $value->length())
      $mask = $mask->replace('\?'.$charMask, '');
    if($mask->match("!{$charMask}!")->count() > $value->length())
      $value = new StringManipulator(sprintf('%-'.$mask->match("!{$charMask}!")->count().'s', $value));
      
    return vsprintf($mask->replace('\?'.$charMask, $charMask)->replace($charMask, '%s'), $value->split());
      
  }
}