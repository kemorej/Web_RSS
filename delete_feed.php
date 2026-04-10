<?php
$feedsXml = simplexml_load_file('feeds.xml');
$feedId = (int)$_GET['id'];
$feed = $feedsXml->xpath("//feed[@id='$feedId']")[0];

if ($feed) {
    $dom = dom_import_simplexml($feed);
    $dom->parentNode->removeChild($dom);
    $feedsXml->asXML('feeds.xml');
}

header("Location: index.php");
exit;
?>