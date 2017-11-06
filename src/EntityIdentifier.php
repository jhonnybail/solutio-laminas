<?php

namespace Solutio;

trait EntityIdentifierTrait
{
  protected static $primaryKeys = ['id'];
  
  /**
   * @var string
   *
   * @ORM\Id
   * @ORM\Column(name="id", type="integer", nullable=false)
   */
  private $id;
  
  public function getId()
  {
    return $this->id;
  }
  
  public function setId($id)
  {
    $this->id = $id;
    return $this;
  }
}