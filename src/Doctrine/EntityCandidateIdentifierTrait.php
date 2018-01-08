<?php

namespace Solutio\Doctrine;

trait EntityCandidateIdentifierTrait
{
  /**
   * @ORM\Column(name="id", type="string", length=36, nullable=false)
   */
  private $id;
  
  public function getId()
  {
    return $this->id;
  }
  
  public function setId($id)
  {
    $this->id                  = $id;
    if($this instanceof \Solutio\EntityInterface)
      $this->setChangedValue('id', $id);
    return $this;
  }
}