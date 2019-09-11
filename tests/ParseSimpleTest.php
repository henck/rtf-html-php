<?php

use PHPUnit\Framework\TestCase;
use RtfHtmlPhp\Document;
use RtfHtmlPhp\Html\HtmlFormatter;

final class ParseSimpleTest extends TestCase
{
  public function testParseSimple(): void
  {
    $rtf = file_get_contents("tests/rtf/hello-world.rtf");
    $document = new Document($rtf);
    $this->assertTrue(true);
  }

  public function testParseSimpleHtml(): void
  {
    $rtf = file_get_contents("tests/rtf/hello-world.rtf");
    $document = new Document($rtf);
    $formatter = new HtmlFormatter();
    $html = $formatter->Format($document);    

    $this->assertEquals(
      '<p><span style="font-family:Calibri;font-size:15px;">Hello, world.</span></p>',
      $html
    );
  }  
}
