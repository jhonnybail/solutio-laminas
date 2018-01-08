<?php

namespace Solutio\Doctrine;

trait EntityIdentifierTrait
{
  /**
   * @var string
   *
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="NONE")
   * @ORM\Column(name="id", type="integer", nullable=false)
   */
  private $id;
  
  public function getId()
  {
    return $this->id;
  }
  
  public function setId($id)
  {
    $this->id = is_numeric($id) ? (int) $id : $id;
    if($this instanceof \Solutio\EntityInterface)
      $this->setChangedValue('id', $this->id);
    return $this;
  }
}