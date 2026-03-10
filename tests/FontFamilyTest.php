<?php

use PHPUnit\Framework\TestCase;
use RtfHtmlPhp\Document;
use RtfHtmlPhp\Html\HtmlFormatter;

final class FontFamilyTestTest extends TestCase
{
  public function testParseFontFamilyHtml(): void
  {
    $rtf = file_get_contents("tests/rtf/fonts.rtf");
    $document = new Document($rtf);
    $formatter = new HtmlFormatter();
    $html = $formatter->Format($document);    

    $this->assertEquals(
      '<p><span style="font-family:Arial,sans-serif;font-size:15px;">Hello, world.</span></p>',
      $html
    );
  }  
}
