<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link rel="stylesheet" href="styles.css">
    <title>Importer un fichier OPML</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 600px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            padding: 10px 15px;
            background: #0066cc;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #0052a3;
        }
        .cancel-button {
            background: #cccccc;
            margin-left: 10px;
        }
        .cancel-button:hover {
            background: #bbbbbb;
        }
        .success {
            color: green;
            margin-top: 15px;
        }
        .error {
            color: red;
            margin-top: 15px;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Importer un fichier OPML</h1>

        <form action="import_opml.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="opml_file">Sélectionnez un fichier OPML :</label>
                <input type="file" id="opml_file" name="opml_file" accept=".opml,.xml" required>
            </div>

            <div class="form-group">
                <label for="default_category">Catégorie par défaut :</label>
                <input type="text" id="default_category" name="default_category" value="Importés" required>
            </div>

            <div class="form-group">
                <label for="default_display">Option d'affichage par défaut :</label>
                <select id="default_display" name="default_display" required>
                    <option value="10">10 derniers articles complets</option>
                    <option value="10_titles">Uniquement les 10 derniers titres</option>
                    <option value="recent">Articles complets des 2 derniers jours</option>
                    <option value="recent_titles">Uniquement les titres des 2 derniers jours</option>
                    <option value="latest">Article le plus récent + 9 titres</option>
                </select>
            </div>

            <div class="form-group">
                <label for="max_age_days">Nombre de jours max (si option "récente") :</label>
                <input type="number" id="max_age_days" name="max_age_days" min="1" value="2">
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-file-import"></i> Importer</button>
			<a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Annuler</a>	
        </form>

        <?php
        function parseOpmlOutlines($outlines, &$feeds, $defaultCategory) {
            $feedCount = 0;
            foreach ($outlines as $outline) {
                if (isset($outline['xmlUrl']) && !empty((string)$outline['xmlUrl'])) {
                    // C'est un flux RSS/Atom
                    $feeds[] = [
                        'text' => (string)$outline['text'],
                        'xmlUrl' => (string)$outline['xmlUrl'],
                        'category' => $defaultCategory
                    ];
                    $feedCount++;
                } elseif (isset($outline->outline)) {
                    // C'est un dossier, parcourir récursivement
                    $subCategory = (string)$outline['text'];
                    $feedCount += parseOpmlOutlines($outline->outline, $feeds, $subCategory ?: $defaultCategory);
                }
            }
            return $feedCount;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_FILES['opml_file']) || $_FILES['opml_file']['error'] !== UPLOAD_ERR_OK) {
                echo '<p class="error">Erreur lors du téléchargement du fichier : ' . $_FILES['opml_file']['error'] . '</p>';
            } else {
                $opmlFile = $_FILES['opml_file']['tmp_name'];
                $defaultCategory = htmlspecialchars($_POST['default_category']);
                $defaultDisplay = htmlspecialchars($_POST['default_display']);
                $maxAgeDays = (int)$_POST['max_age_days'];

                // Charger le fichier OPML
                $opml = simplexml_load_file($opmlFile);
                if ($opml === false) {
                    echo '<p class="error">Erreur : Le fichier n\'est pas un OPML valide.</p>';
                    // Afficher les erreurs XML pour le débogage
                    $errors = libxml_get_errors();
                    foreach ($errors as $error) {
                        echo "<p class='error'>" . $error->message . "</p>";
                    }
                    libxml_clear_errors();
                } else {
                    // Charger le fichier feeds.xml existant
                    $feedsXml = simplexml_load_file('feeds.xml');
                    if ($feedsXml === false) {
                        echo '<p class="error">Erreur : Impossible de charger feeds.xml.</p>';
                    } else {
                        // Calculer le nouvel ID
                        $ids = [];
                        foreach ($feedsXml->feed as $feed) {
                            $ids[] = (int)$feed['id'];
                        }
                        $nextId = empty($ids) ? 1 : max($ids) + 1;

                        // Extraire tous les flux de l'OPML
                        $feeds = [];
                        $totalFeeds = parseOpmlOutlines($opml->body->outline, $feeds, $defaultCategory);

                        if ($totalFeeds === 0) {
                            echo '<p class="error">Aucun flux trouvé dans le fichier OPML.</p>';
                            // Afficher la structure OPML pour le débogage
                            echo "<h3>Structure du fichier OPML :</h3>";
                            echo "<pre>" . htmlspecialchars(print_r($opml->body->outline, true)) . "</pre>";
                        } else {
                            // Ajouter les flux à feeds.xml
                            foreach ($feeds as $feedData) {
                                $newFeed = $feedsXml->addChild('feed');
                                $newFeed->addAttribute('id', $nextId++);
                                $newFeed->addAttribute('category', $feedData['category']);
                                $newFeed->addChild('name', $feedData['text']);
                                $newFeed->addChild('url', $feedData['xmlUrl']);
                                $newFeed->addChild('display', $defaultDisplay);

                                if (strpos($defaultDisplay, 'recent') !== false) {
                                    $newFeed->addChild('max_age_days', $maxAgeDays);
                                }
                            }

                            // Sauvegarder le fichier XML
                            $feedsXml->asXML('feeds.xml');

                            echo "<p class='success'>$totalFeeds flux importés avec succès ! <a href='index.php'>Retour à l'accueil</a></p>";
                        }
                    }
                }
            }
        }
        ?>
    </div>
</body>
</html>