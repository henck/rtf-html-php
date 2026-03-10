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
      '<p><span style="font-family:Symbol;">&#183;&nbsp;</span><span style="font-family:Calibri;font-size:15px;">A</span></p><p><span style="font-family:Symbol;font-size:15px;">&#183;&nbsp;</span><span style="font-family:Calibri;font-size:15px;">B</span></p><p><span style="font-family:Symbol;font-size:15px;">&#183;&nbsp;</span><span style="font-family:Calibri;font-size:15px;">C</span></p>',
      $html
    );
  }  
}
