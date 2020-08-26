<?php
    error_reporting(-1);
    ini_set('display_errors', 'on');
    require_once __DIR__ . '/vendor/autoload.php';

    use RtfHtmlPhp\Document;
    use RtfHtmlPhp\Html\HtmlFormatter;

    $original  = file_get_contents("sss.rtf");
    // var_dump($original);exit;
    // $final_result = str_replace(array("\r", "\f", "\v", "\t", "\""), array("\\r", "\\f", " \\v", "\\t", '\\"'), $original);

    $document = new Document($original); // or use a string directly
    $formatter = new HtmlFormatter('UTF-8');
    $r = $formatter->Format($document);
    file_put_contents('rtf.html', $r);
    echo $r;
?>