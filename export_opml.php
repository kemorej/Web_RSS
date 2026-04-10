<?php
header('Content-Type: text/x-opml');
header('Content-Disposition: attachment; filename="feeds.opml"');

$feedsXml = simplexml_load_file('feeds.xml');
?>
<opml version="1.0">
    <head>
        <title>Export OPML - Mes flux RSS/Atom</title>
    </head>
    <body>
        <?php
        foreach ($feedsXml->feed as $feed) {
            $name = (string)$feed->name;
            $url = (string)$feed->url;
            echo "<outline text=\"$name\" xmlUrl=\"$url\" />\n";
        }
        ?>
    </body>
</opml>

<!-- Ajoute un bouton pour revenir à l'accueil après l'export -->
<script>
    window.onload = function() {
        document.body.innerHTML += '<div style="text-align: center; margin-top: 20px;"><a href="index.php" class="btn btn-primary">Retour à l\'accueil</a></div>';
    };
</script>