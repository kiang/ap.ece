<?php
$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
use Symfony\Component\CssSelector\CssSelectorConverter;

$converter = new CssSelectorConverter();

$json = json_decode(file_get_contents($basePath . '/file.json'), true);
$rawPath = $basePath . '/raw/records';
if(!file_exists($rawPath)) {
    mkdir($rawPath, 0777);
}
$dataPath = $basePath . '/data';
if(!file_exists($dataPath)) {
    mkdir($dataPath, 0777);
}
$oFh = array();
foreach($json AS $record) {
    $recordFile = $rawPath . '/' . $record['id'] . '.html';
    if(!file_exists($recordFile)) {
        file_put_contents($recordFile, file_get_contents($record['url']));
    }
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . file_get_contents($recordFile));
    $xpath = new DOMXpath($doc);
    $elements = $xpath->query($converter->toXPath('span'));

    foreach($elements AS $element) {
        $fieldKeys = explode('_', $element->getAttribute('id'));
        if(isset($fieldKeys[1])) {
            $record[$fieldKeys[1]] = $element->nodeValue;
        }
    }

    $elements = $xpath->query($converter->toXPath('a'));
    foreach($elements AS $element) {
        $record['ref'] = $element->nodeValue;
    }

    if(!isset($oFh[$record['city']])) {
        $oFh[$record['city']] = fopen($dataPath . '/' . $record['city'] . '.csv', 'w');
        fputcsv($oFh[$record['city']], array('id', '學校', '資料網址', '縣市', '鄉鎮市區', '公私立', '住址', '電話', '核定人數', '營運狀態', '處分日期', '裁處文號', '違反之規定', '負責人/行為人', '處分內容', '處分依據'));
    }
    fputcsv($oFh[$record['city']], $record);
}