<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedId = isset($_POST['feed_id']) ? $_POST['feed_id'] : '';
    $collapsed = isset($_POST['collapsed']) ? $_POST['collapsed'] === 'true' : false;

    if (empty($feedId)) {
        http_response_code(400);
        echo "Erreur : ID de flux manquant.";
        exit;
    }

    $feedsXml = simplexml_load_file('feeds.xml');
    if ($feedsXml === false) {
        http_response_code(500);
        echo "Erreur : Impossible de charger feeds.xml.";
        exit;
    }

    // Trouver le flux et mettre à jour l'attribut collapsed
    $feed = $feedsXml->xpath("//feed[@id='$feedId']")[0];
    if ($feed) {
        $feed['collapsed'] = $collapsed ? 'true' : 'false';
        $feedsXml->asXML('feeds.xml');
        echo "OK";
    } else {
        http_response_code(404);
        echo "Erreur : Flux non trouvé.";
    }
}
?>