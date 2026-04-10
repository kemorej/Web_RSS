<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

	<link rel="stylesheet" href="styles.css">
    <title>Ajouter un flux RSS/Atom</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f9f9f9;
        }
        form {
            max-width: 500px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
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
            margin-top: 15px;
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
    </style>
</head>
<body>
    <h1>Ajouter un flux RSS/Atom</h1>

    <form action="add_feed.php" method="post">
        <label for="name">Nom du flux :</label>
        <input type="text" id="name" name="name" required>

        <label for="url">URL du flux (RSS ou Atom) :</label>
        <input type="url" id="url" name="url" required placeholder="Ex: https://linuxfr.org/liens.atom">

        <label for="category">Catégorie (onglet) :</label>
        <input type="text" id="category" name="category" required placeholder="Ex: Actualités, Technologie">

        <label for="display">Option d'affichage :</label>
        <select id="display" name="display" required>
            <option value="10">10 derniers articles complets</option>
            <option value="10_titles">Uniquement les 10 derniers titres</option>
            <option value="recent">Articles complets des 2 derniers jours</option>
            <option value="recent_titles">Uniquement les titres des 2 derniers jours</option>
            <option value="latest">Article le plus récent + 9 titres</option>
        </select>

        <label for="max_age_days">Nombre de jours max (si option "récente") :</label>
        <input type="number" id="max_age_days" name="max_age_days" min="1" value="2">

		<button type="submit" class="btn"><i class="fas fa-save"></i> Ajouter</button>
		<a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Annuler</a>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $feedsXml = simplexml_load_file('feeds.xml');

        // Calculer le nouvel ID
        $ids = [];
        foreach ($feedsXml->feed as $feed) {
            $ids[] = (int)$feed['id'];
        }
        $newId = empty($ids) ? 1 : max($ids) + 1;

        // Ajouter le nouveau flux
        $newFeed = $feedsXml->addChild('feed');
        $newFeed->addAttribute('id', $newId);
        $newFeed->addAttribute('category', htmlspecialchars($_POST['category']));
        $newFeed->addChild('name', htmlspecialchars($_POST['name']));
        $newFeed->addChild('url', htmlspecialchars($_POST['url']));
        $newFeed->addChild('display', htmlspecialchars($_POST['display']));

        if (strpos($_POST['display'], 'recent') !== false) {
            $newFeed->addChild('max_age_days', (int)$_POST['max_age_days']);
        }

        // Sauvegarder le fichier XML
        $feedsXml->asXML('feeds.xml');

        echo "<p style='margin-top: 20px; color: green;'>Flux ajouté avec succès ! <a href='index.php'>Retour à l'accueil</a></p>";
    }
    ?>
</body>
</html>