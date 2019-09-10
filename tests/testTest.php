<?php

use PHPUnit\Framework\TestCase;

final class TestTest extends TestCase
{
  public function testParseSimple(): void
  {
    $reader = new RtfReader();
    $rtf = file_get_contents("tests/hello-world.rtf");
    $result = $reader->Parse($rtf);
    $this->assertTrue($result);
  }

  public function testParseSimpleHtml(): void
  {
    $reader = new RtfReader();
    $rtf = file_get_contents("tests/hello-world.rtf");
    $reader->Parse($rtf);
    $formatter = new RtfHtml();
    $html = $formatter->Format($reader->root);    

    $this->assertEquals(
      $html,
      '<p><span style="font-size:15px;">Hello, world.</span></p>'
    );
  }  
}
