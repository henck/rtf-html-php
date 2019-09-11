<?php

namespace RtfHtmlPhp;

class Group extends Element
{
  public $parent;
  public $children;

  /*
   * Create a new Group, with no parent and no children.
   */
  public function __construct()
  {
    $this->parent = null;
    $this->children = array();
  }

  public function GetType()
  {
    // No children?
    if(sizeof($this->children) == 0) return null;
    // First child not a control word?
    $child = $this->children[0];
    if($child instanceof ControlWord)
    return $child->word;
    elseif ($child instanceof ControlSymbol)
      return ($child->symbol == '*') ? '*' : null;
    
    return null;
  }    

  public function IsDestination()
  {
    // No children?
    if(sizeof($this->children) == 0) return null;
    // First child not a control symbol?
    $child = $this->children[0];
    if(!$child instanceof ControlSymbol) return null;
    return $child->symbol == '*';
  }

  public function dump($level = 0)
  {
    $this->Indent($level);
    echo "{\n";

    foreach($this->children as $child)
    {
      // Skip some group types:
      if($child instanceof Group) {
        if ($child->GetType() == "fonttbl") continue;
        if ($child->GetType() == "colortbl") continue;
        if ($child->GetType() == "stylesheet") continue;
        if ($child->GetType() == "info") continue;
        // Skip any pictures:
        if (substr($child->GetType(), 0, 4) == "pict") continue;
        if ($child->IsDestination()) continue;
      }
      $child->dump($level + 2);
    }

    $this->Indent($level);
    echo "}\n";
  }
}
