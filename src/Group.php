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
    // No children? Then the group type is null.
    if(sizeof($this->children) == 0) return null;
    
    // If the first child is a control word, then
    // the group type is the word.
    $child = $this->children[0];
    if($child instanceof ControlWord) return $child->word;

    // If the first child is a control symbol, then
    // the group type is * for a special symbol, or null.
    elseif ($child instanceof ControlSymbol) {
      return ($child->symbol == '*') ? '*' : null;
    }
    
    // If first child is neither word nor symbol, then
    // group type is null.
    return null;
  }    

  // If a group contains a * symbol as its first child,
  // then it is a destination group.
  public function IsDestination()
  {
    // If group has no children, then destination is null.
    if(sizeof($this->children) == 0) return null;
    // First child not a control symbol?
    $child = $this->children[0];
    if(!$child instanceof ControlSymbol) return null;
    return $child->symbol == '*';
  }

  //
  // Convert Group to string for debugging purposes.
  //
  public function toString(int $level = 0)
  {
    $str = str_repeat("  ", $level) .  "{\n";

    foreach($this->children as $child)
    {
      /* 
      // Skip some group types:
      if($child instanceof Group) {
        if ($child->GetType() == "fonttbl") continue;
        if ($child->GetType() == "colortbl") continue;
        if ($child->GetType() == "stylesheet") continue;
        if ($child->GetType() == "info") continue;
        // Skip any pictures:
        if (substr($child->GetType(), 0, 4) == "pict") continue;
        if ($child->IsDestination()) continue;
      } */
      $str .= $child->toString($level + 1);
    }

    return $str . str_repeat("  ", $level) . "}\n";
  }
}
