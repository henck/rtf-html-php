<?php 

namespace RtfHtmlPhp;

class ControlSymbol extends Element
{
  public $symbol;
  public $parameter = 0;

  public function toString(int $level)
  {
    return str_repeat("  ", $level) . "SYMBOL {$this->symbol} ({$this->parameter})\n";
  }    
}