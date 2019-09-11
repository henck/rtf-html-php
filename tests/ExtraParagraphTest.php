<?php

use PHPUnit\Framework\TestCase;
use RtfHtmlPhp\Reader;
use RtfHtmlPhp\Html\Html;

final class ExtraParagraphTest extends TestCase
{
  public function testExtraParagraph(): void
  {
    $reader = new Reader();
    $rtf = file_get_contents("tests/rtf/extra-closing-paragraph.rtf");
    $reader->Parse($rtf);
    $formatter = new Html();
    $html = $formatter->Format($reader->root);    

    $this->assertEquals(
      '<p><span style="font-weight:bold;font-size:16px;color:#000000;">Conditions<br/></span><span style="font-size:16px;color:#000000;">&#1;Delivery: FCA in our warehouse in Rotterdam<br/>&#1;Lead Time: 25 working days after confirmation, subject to prior sale<br/>&#1;Payment: 60 days after invoice date<br/>&#1;Quote validity: 30 days',
      $html
    );
  }  
}
