<?php

use PHPUnit\Framework\TestCase;
use RtfHtmlPhp\Reader;

final class EmptyStringTest extends TestCase
{
  public function testParseEmptyString(): void
  {
    $reader = new Reader();
    $result = $reader->Parse("");
    $this->assertTrue($result);
  }
}
