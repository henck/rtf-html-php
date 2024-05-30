<?php

use PHPUnit\Framework\TestCase;
use RtfHtmlPhp\Document;
use RtfHtmlPhp\Html\HtmlFormatter;

final class ExtraParagraphTest extends TestCase
{
  public function testExtraParagraph(): void
  {
    $rtf = file_get_contents(__DIR__ ."/rtf/extra-closing-paragraph.rtf");

    $document = new Document($rtf);
    $formatter = new HtmlFormatter();
    $html = $formatter->Format($document);

    $this->assertEquals(
      '<p><span style="font-weight:bold;font-family:Calibri;font-size:16px;color:#000000;">Conditions<br/></span><span style="font-family:Calibri;font-size:16px;color:#000000;">Delivery: FCA in our warehouse in Rotterdam<br/>Lead Time: 25 working days after confirmation, subject to prior sale<br/>Payment: 60 days after invoice date<br/>Quote validity: 30 days</span></p>',
      $html
    );
  }  
}
