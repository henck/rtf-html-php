<?php

namespace RtfHtmlPhp;

class ControlWord extends Element
{
  public $word;
  public $parameter;

  public function dump($level)
  {
    $this->Indent($level);
    echo "WORD {$this->word} ({$this->parameter})\n";
  }
}
