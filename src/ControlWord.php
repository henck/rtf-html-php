<?php

namespace RtfHtmlPhp;

class ControlWord extends Element
{
  public $word;
  public $parameter;

  public function dump($level)
  {
    echo "<div style='color:green'>";
    $this->Indent($level);
    echo "WORD {$this->word} ({$this->parameter})";
    echo "</div>";
  }
}
