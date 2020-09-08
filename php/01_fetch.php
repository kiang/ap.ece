<?php
$basePath = dirname(__DIR__);

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
    $recordFile = $rawPath . '/' . $record['city'] . '_' . md5($record['id']) . '.html';
    if(!file_exists($recordFile)) {
        file_put_contents($recordFile, file_get_contents($record['url']));
    }
    $html = file_get_contents($recordFile);
    $pos = strpos($html, '<td align="center">');
    while(false !== $pos) {
        $posEnd = strpos($html, '</tr>', $pos);
        $table = substr($html, $pos, $posEnd - $pos);
        $table = str_replace('&nbsp;', '', $table);
        $parts = explode('</span>', $table);
        foreach($parts AS $k => $v) {
            $parts[$k] = substr($v, strrpos($v, '>') + 1);
        }
        array_pop($parts);
        $refPos = strpos($table, '<a id=');
        $refPos = strpos($table, '>', $refPos) + 1;
        $refPosEnd = strpos($table, '<', $refPos);
        $parts[] = substr($table, $refPos, $refPosEnd - $refPos);
        
        if(!isset($oFh[$record['city']])) {
            $oFh[$record['city']] = fopen($dataPath . '/' . $record['city'] . '.csv', 'w');
            fputcsv($oFh[$record['city']], array('id', '學校', '資料網址', '縣市', '鄉鎮市區', '公私立', '住址', '電話', '核定人數', '營運狀態', '處分日期', '裁處文號', '違反之規定', '負責人/行為人', '處分內容', '處分依據'));
        }
        fputcsv($oFh[$record['city']], array_merge($record, $parts));

        $pos = strpos($html, '<td align="center">', $posEnd);
    }
}