<?php
    error_reporting(-1);
    ini_set('display_errors', 'on');
    require_once __DIR__ . '/vendor/autoload.php';

    use RtfHtmlPhp\Document;
    use RtfHtmlPhp\Html\HtmlFormatter;

    $original  = file_get_contents("tests/rtf/hello-world.rtf");

    $document = new Document($original); // or use a string directly
    $formatter = new HtmlFormatter('UTF-8');
    $r = $formatter->Format($document);
    file_put_contents('rtf.html', $r);
    echo $r;
?>