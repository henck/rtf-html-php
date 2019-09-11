<?php 

namespace RtfHtmlPhp;

/*
 * Element is the parent class of all RTF elements,
 * like Group, ControlWord and ControlSymbol.
 */
class Element
{
  protected function Indent($level)
  {
    for($i = 0; $i < $level * 2; $i++) echo " ";
  }
}
