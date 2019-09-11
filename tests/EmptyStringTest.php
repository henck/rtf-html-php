<?php

use PHPUnit\Framework\TestCase;
use RtfHtmlPhp\Document;

final class EmptyStringTest extends TestCase
{
  public function testParseEmptyString(): void
  {
    $document = new Document("");
    $this->assertTrue(true);
  }
}
