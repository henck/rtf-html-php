<?php

use PHPUnit\Framework\TestCase;
use RtfHtmlPhp\Reader;
use RtfHtmlPhp\Html\Html;

final class BulletsTest extends TestCase
{
  public function testBullets(): void
  {
    $reader = new Reader();
    $rtf = file_get_contents("tests/rtf/bullets.rtf");
    $reader->Parse($rtf);
    $formatter = new Html();
    $html = $formatter->Format($reader->root);    

    $this->assertEquals(
      '<p><span>&#183;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="font-size:15px;">A</span></p><p><span style="font-size:15px;">&#183;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;B</span></p><p><span style="font-size:15px;">&#183;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C</span></p>',
      $html
    );
  }  
}
