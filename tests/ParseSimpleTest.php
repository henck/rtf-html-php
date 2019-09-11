<?php

use PHPUnit\Framework\TestCase;
use RtfHtmlPhp\Reader;
use RtfHtmlPhp\Html\Html;

final class ParseSimpleTest extends TestCase
{
  public function testParseSimple(): void
  {
    $reader = new Reader();
    $rtf = file_get_contents("tests/rtf/hello-world.rtf");
    $result = $reader->Parse($rtf);
    $this->assertTrue($result);
  }

  public function testParseSimpleHtml(): void
  {
    $reader = new Reader();
    $rtf = file_get_contents("tests/rtf/hello-world.rtf");
    $reader->Parse($rtf);
    $formatter = new Html();
    $html = $formatter->Format($reader->root);    

    $this->assertEquals(
      '<p><span style="font-size:15px;">Hello, world.</span></p>',
      $html
    );
  }  
}
