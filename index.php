<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecteur de flux RSS/Atom</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link rel="stylesheet" href="styles.css">
	<style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
            background-color: #f9f9f9;
        }
        /* Style commun pour tous les boutons */
        .btn {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s, color 0.3s;
            margin: 3px;
            font-size: 0.9em;
        }
        .btn-primary {
            background-color: #0066cc;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0052a3;
        }
        .btn-secondary {
            background-color: #ff9800;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #e68a00;
        }
        .btn-danger {
            background-color: #cc0000;
            color: white;
        }
        .btn-danger:hover {
            background-color: #a30000;
        }
        .btn-link {
            background-color: transparent;
            color: #0066cc;
            text-decoration: underline;
        }
        .btn-link:hover {
            color: #0052a3;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
            flex-wrap: wrap;
        }
        .tab-button {
            padding: 10px 15px;
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
            border: 1px solid #e0e0e0;
            border-bottom: none;
            cursor: pointer;
            margin-bottom: -1px;
        }
        .tab-button.active {
            background: #ffffff;
            color: #0066cc;
            border-bottom: 1px solid #ffffff;
            font-weight: bold;
        }
        .tab-button:hover {
            background: #e9e9e9;
        }
        .feed {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e0e0e0;
        }
        .feed-name {
            font-weight: bold;
            font-size: 1.2em;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        .feed-name a {
            color: #333;
            text-decoration: none;
        }
        .feed-name a:hover {
            text-decoration: underline;
        }
        .feed-item {
            margin-bottom: 20px;
            padding-left: 10px;
            border-left: 3px solid #0066cc;
        }
        .feed-item-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .feed-item-link {
            color: #0066cc;
            text-decoration: none;
        }
        .feed-item-link:hover {
            text-decoration: underline;
        }
        .feed-item-description {
            color: #666;
            margin: 10px 0;
        }
        .feed-item-date {
            font-size: 0.8em;
            color: #999;
            margin-left: 5px;
        }
        .feed-item-image {
            max-width: 100%;
            height: auto;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .feed-image {
            max-width: 150px;
            height: auto;
            margin: 10px 0;
            border-radius: 4px;
            float: right;
        }
        .article-image {
            max-width: 100%;
            height: auto;
            margin: 10px 0;
            border-radius: 4px;
        }
        .date-indicator {
            font-size: 0.8em;
            color: #999;
            font-style: italic;
            margin-left: 5px;
        }
        .buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .back-to-top {
            text-align: right;
            margin-top: 10px;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <a name="top"></a>
    <h1>Lecteur de flux RSS/Atom</h1>

    <div class="buttons">
        <a href="add_feed.php" class="btn btn-primary">+ Ajouter un flux</a>
        <a href="export_opml.php" class="btn btn-secondary">Exporter (OPML)</a>
        <a href="import_opml.php" class="btn btn-secondary">Importer OPML</a>
    </div>

    <?php
    function filterRecentItems($items, $maxAgeDays) {
        $filteredItems = [];
        $now = time();
        $maxAgeSeconds = $maxAgeDays * 24 * 60 * 60;
        foreach ($items as $item) {
            $pubDate = strtotime((string)($item->published ?: $item->updated ?: $item->pubDate));
            if ($now - $pubDate <= $maxAgeSeconds) {
                $filteredItems[] = $item;
            }
        }
        return $filteredItems;
    }

    function getArticleImage($item) {
        if (isset($item->enclosure) && strpos((string)$item->enclosure['type'], 'image/') === 0) {
            return (string)$item->enclosure['url'];
        }
        $media = $item->children('http://search.yahoo.com/mrss/');
        if (isset($media->content) && strpos((string)$media->content['type'], 'image/') === 0) {
            return (string)$media->content['url'];
        }
        if (isset($media->thumbnail)) {
            return (string)$media->thumbnail['url'];
        }
        if (isset($item->content) || isset($item->description)) {
            $content = (string)($item->content ?: $item->description);
            if (preg_match('/<img[^>]+src="([^"]+)"/i', $content, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    function getFeedItems($feed) {
        if (isset($feed->entry)) {
            return $feed->entry;
        } else {
            return $feed->channel->item;
        }
    }

    function getFeedImage($feed) {
        if (isset($feed->logo)) {
            return (string)$feed->logo;
        } elseif (isset($feed->channel->image->url)) {
            return (string)$feed->channel->image->url;
        }
        return null;
    }

    function getFeedLink($feed) {
        if (isset($feed->link['href'])) {
            return (string)$feed->link['href'];
        } elseif (isset($feed->channel->link)) {
            return (string)$feed->channel->link;
        } elseif (isset($feed->link[0]['href'])) {
            foreach ($feed->link as $link) {
                if ((string)$link['rel'] === 'alternate') {
                    return (string)$link['href'];
                }
            }
            return (string)$feed->link[0]['href'];
        }
        return null;
    }

    function getArticleDate($item) {
        return (string)($item->published ?: $item->updated ?: $item->pubDate);
    }

    function isOldArticle($pubDate) {
        $now = time();
        $articleDate = strtotime($pubDate);
        return date('Y-m-d', $articleDate) < date('Y-m-d', $now);
    }

    function formatDate($pubDate) {
        return date('d/m/Y', strtotime($pubDate));
    }
    ?>

    <?php
    $feedsXml = simplexml_load_file('feeds.xml');
    if ($feedsXml === false) {
        die('<p class="error">Erreur : Impossible de charger le fichier feeds.xml.</p>');
    }

    // Récupérer toutes les catégories uniques
    $categories = [];
    foreach ($feedsXml->feed as $feed) {
        $category = (string)$feed['category'];
        if (!in_array($category, $categories)) {
            $categories[] = $category;
        }
    }

    // Catégorie sélectionnée (par défaut : première catégorie)
    $selectedCategory = isset($_GET['category']) ? $_GET['category'] : (count($categories) > 0 ? $categories[0] : '');
    ?>

    <!-- Onglets -->
    <?php if (count($categories) > 0): ?>
    <div class="tabs">
        <?php foreach ($categories as $category): ?>
            <a href="?category=<?php echo urlencode($category); ?>" class="tab-button <?php echo ($category === $selectedCategory) ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($category); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php
    foreach ($feedsXml->feed as $feed) {
        $feedCategory = (string)$feed['category'];
        if ($feedCategory !== $selectedCategory) {
            continue;
        }

        $feedId = (string)$feed['id'];
        $feedName = (string)$feed->name;
        $feedUrl = (string)$feed->url;
        $display = (string)$feed->display;
        $maxAgeDays = isset($feed->max_age_days) ? (int)$feed->max_age_days : 2;

        echo "<div class='feed clearfix'>";

        // Chargement du flux
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]
        ]);

        $feedContent = @file_get_contents($feedUrl, false, $context);

        if ($feedContent === false) {
            echo "<div class='feed-name'>";
            echo "<span>$feedName</span>";
            echo "<div>";
            echo "<a href='edit_feed.php?id=$feedId' class='btn btn-secondary'><i class='fas fa-edit'></i> Modifier</a>";
            echo "<a href='delete_feed.php?id=$feedId' class='btn btn-danger' onclick='return confirm(\"Voulez-vous vraiment supprimer ce flux ?\");'><i class='fas fa-trash'></i> Supprimer</a>";
            echo "</div>";
            echo "</div>";
            echo "<p class='error'>Impossible de charger le flux pour $feedName (URL inaccessible ou timeout).</p>";
            continue;
        }

        $feedData = simplexml_load_string($feedContent);

        if ($feedData === false) {
            echo "<div class='feed-name'>";
            echo "<span>$feedName</span>";
            echo "<div>";
            echo "<a href='edit_feed.php?id=$feedId' class='btn btn-secondary'><i class='fas fa-edit'></i> Modifier</a>";
            echo "<a href='delete_feed.php?id=$feedId' class='btn btn-danger' onclick='return confirm(\"Voulez-vous vraiment supprimer ce flux ?\");'><i class='fas fa-trash'></i> Supprimer</a>";
            echo "</div>";
            echo "</div>";
            $errors = libxml_get_errors();
            $errorMsg = "Erreur dans le flux pour $feedName : ";
            foreach ($errors as $error) {
                $errorMsg .= $error->message . " ";
            }
            libxml_clear_errors();
            echo "<p class='error'>$errorMsg</p>";
            continue;
        }

        $feedLink = getFeedLink($feedData);

        echo "<div class='feed-name'>";
        if ($feedLink) {
            echo "<a href='" . htmlspecialchars($feedLink) . "' target='_blank'>$feedName</a>";
        } else {
            echo "<span>$feedName</span>";
        }
        echo "<div>";
        echo "<a href='edit_feed.php?id=$feedId' class='btn btn-secondary'><i class='fas fa-edit'></i> Modifier</a>";
        echo "<a href='delete_feed.php?id=$feedId' class='btn btn-danger' onclick='return confirm(\"Voulez-vous vraiment supprimer ce flux ?\");'><i class='fas fa-times'></i> Supprimer</a>";
        echo "</div>";
        echo "</div>";

        $items = getFeedItems($feedData);
        $feedImageUrl = getFeedImage($feedData);

        if ($feedImageUrl) {
            echo "<img src='" . htmlspecialchars($feedImageUrl) . "' class='feed-image' alt='Image du flux $feedName' />";
        }

        if ($display === 'recent') {
            $items = filterRecentItems($items, $maxAgeDays);
            foreach ($items as $item) {
                $title = (string)($item->title ?: $item->name);
                $link = (string)($item->link['href'] ?: $item->link);
                $pubDate = getArticleDate($item);
                $isOld = isOldArticle($pubDate);

                echo "<div class='feed-item'>";
                $articleImage = getArticleImage($item);
                if ($articleImage) {
                    echo "<img src='" . htmlspecialchars($articleImage) . "' class='article-image' alt='Image de l\'article' />";
                }
                echo "<div class='feed-item-title'>";
                echo "<a href='" . htmlspecialchars($link) . "' class='feed-item-link' target='_blank'>$title</a>";
                if ($isOld) {
                    echo "<span class='date-indicator'> (" . formatDate($pubDate) . ")</span>";
                }
                echo "</div>";
                $description = (string)($item->content ?: $item->summary ?: $item->description);
                echo "<div class='feed-item-description'>$description</div>";
                echo "<div class='feed-item-date'>" . date('d/m/Y H:i', strtotime($pubDate)) . "</div>";
                echo "</div>";
            }
        } elseif ($display === 'recent_titles') {
            $items = filterRecentItems($items, $maxAgeDays);
            foreach ($items as $item) {
                $title = (string)($item->title ?: $item->name);
                $link = (string)($item->link['href'] ?: $item->link);
                $pubDate = getArticleDate($item);
                $isOld = isOldArticle($pubDate);

                echo "<div class='feed-item'>";
                $articleImage = getArticleImage($item);
                if ($articleImage) {
                    echo "<img src='" . htmlspecialchars($articleImage) . "' style='max-width: 100px; display: inline-block; margin-right: 10px; vertical-align: middle;' />";
                }
                echo "<div class='feed-item-title' style='display: inline-block;'>";
                echo "• <a href='" . htmlspecialchars($link) . "' class='feed-item-link' target='_blank'>$title</a>";
                if ($isOld) {
                    echo "<span class='date-indicator'> (" . formatDate($pubDate) . ")</span>";
                }
                echo "</div>";
                echo "</div>";
            }
        } elseif ($display === '10_titles') {
            $limit = min(10, count($items));
            for ($i = 0; $i < $limit; $i++) {
                if (isset($items[$i])) {
                    $item = $items[$i];
                    $title = (string)($item->title ?: $item->name);
                    $link = (string)($item->link['href'] ?: $item->link);
                    $pubDate = getArticleDate($item);
                    $isOld = isOldArticle($pubDate);

                    echo "<div class='feed-item'>";
                    $articleImage = getArticleImage($item);
                    if ($articleImage) {
                        echo "<img src='" . htmlspecialchars($articleImage) . "' style='max-width: 100px; display: inline-block; margin-right: 10px; vertical-align: middle;' />";
                    }
                    echo "<div class='feed-item-title' style='display: inline-block;'>";
                    echo "• <a href='" . htmlspecialchars($link) . "' class='feed-item-link' target='_blank'>$title</a>";
                    if ($isOld) {
                        echo "<span class='date-indicator'> (" . formatDate($pubDate) . ")</span>";
                    }
                    echo "</div>";
                    echo "</div>";
                }
            }
        } elseif ($display === 'latest') {
            if (isset($items[0])) {
                $latestItem = $items[0];
                $title = (string)($latestItem->title ?: $latestItem->name);
                $link = (string)($latestItem->link['href'] ?: $latestItem->link);
                $pubDate = getArticleDate($latestItem);
                $isOld = isOldArticle($pubDate);

                echo "<div class='feed-item'>";
                $articleImage = getArticleImage($latestItem);
                if ($articleImage) {
                    echo "<img src='" . htmlspecialchars($articleImage) . "' class='article-image' alt='Image de l\'article' />";
                }
                echo "<div class='feed-item-title'>";
                echo "<a href='" . htmlspecialchars($link) . "' class='feed-item-link' target='_blank'>$title</a>";
                if ($isOld) {
                    echo "<span class='date-indicator'> (" . formatDate($pubDate) . ")</span>";
                }
                echo "</div>";
                $description = (string)($latestItem->content ?: $latestItem->summary ?: $latestItem->description);
                echo "<div class='feed-item-description'>$description</div>";
                echo "<div class='feed-item-date'>" . date('d/m/Y H:i', strtotime($pubDate)) . "</div>";
                echo "</div>";

                echo "<div class='feed-item-title'>9 derniers titres :</div>";
                for ($i = 1; $i < min(10, count($items)); $i++) {
                    if (isset($items[$i])) {
                        $item = $items[$i];
                        $title = (string)($item->title ?: $item->name);
                        $link = (string)($item->link['href'] ?: $item->link);
                        $pubDate = getArticleDate($item);
                        $isOld = isOldArticle($pubDate);

                        echo "<div style='margin-left: 10px; margin-bottom: 5px;'>";
                        $articleImage = getArticleImage($item);
                        if ($articleImage) {
                            echo "<img src='" . htmlspecialchars($articleImage) . "' style='max-width: 50px; display: inline-block; margin-right: 10px; vertical-align: middle;' />";
                        }
                        echo "<span>";
                        echo "• <a href='" . htmlspecialchars($link) . "' class='feed-item-link' target='_blank'>$title</a>";
                        if ($isOld) {
                            echo "<span class='date-indicator'> (" . formatDate($pubDate) . ")</span>";
                        }
                        echo "</span>";
                        echo "</div>";
                    }
                }
            }
        } else {
            $limit = min(10, count($items));
            for ($i = 0; $i < $limit; $i++) {
                if (isset($items[$i])) {
                    $item = $items[$i];
                    $title = (string)($item->title ?: $item->name);
                    $link = (string)($item->link['href'] ?: $item->link);
                    $pubDate = getArticleDate($item);
                    $isOld = isOldArticle($pubDate);

                    echo "<div class='feed-item'>";
                    $articleImage = getArticleImage($item);
                    if ($articleImage) {
                        echo "<img src='" . htmlspecialchars($articleImage) . "' class='article-image' alt='Image de l\'article' />";
                    }
                    echo "<div class='feed-item-title'>";
                    echo "<a href='" . htmlspecialchars($link) . "' class='feed-item-link' target='_blank'>$title</a>";
                    if ($isOld) {
                        echo "<span class='date-indicator'> (" . formatDate($pubDate) . ")</span>";
                    }
                    echo "</div>";
                    $description = (string)($item->content ?: $item->summary ?: $item->description);
                    echo "<div class='feed-item-description'>$description</div>";
                    echo "<div class='feed-item-date'>" . date('d/m/Y H:i', strtotime($pubDate)) . "</div>";
                    echo "</div>";
                }
            }
        }

        // Lien "Retour en haut"
        echo "<div class='back-to-top'>";
        echo "<a href='#top' class='btn btn-link' style='font-size: 0.9em;'><i class='fas fa-turn-up'></i>  Retour en haut</a>";
        echo "</div>";

        echo "</div>"; // Fermeture de la div .feed
    }
    ?>
</body>
</html>