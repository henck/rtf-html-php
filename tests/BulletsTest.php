<?php

use PHPUnit\Framework\TestCase;
use RtfHtmlPhp\Document;
use RtfHtmlPhp\Html\HtmlFormatter;

final class BulletsTest extends TestCase
{
  public function testBullets(): void
  {
    $rtf = file_get_contents("tests/rtf/bullets.rtf");
    $document = new Document($rtf);
    $formatter = new HtmlFormatter();
    $html = $formatter->Format($document);

    $this->assertEquals(
      '<p><span>&#183;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="font-size:15px;">A</span></p><p><span style="font-size:15px;">&#183;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;B</span></p><p><span style="font-size:15px;">&#183;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C</span></p>',
      $html
    );
  }  
}
