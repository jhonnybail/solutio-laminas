<?php

/**
 * Solutio.Me
 *
 * @package     Solutio
 * @link        http://github.com/jhonnybail/solutio-laminas
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio;

/**
 * Classe de excessão para argumentos inválidos.
 */
class InvalidArgumentException extends \InvalidArgumentException
{
  use ExceptionTrait;
}