<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link rel="stylesheet" href="styles.css">
    <title>Modifier un flux RSS/Atom</title>
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
    <h1>Modifier un flux RSS/Atom</h1>

    <?php
    $feedsXml = simplexml_load_file('feeds.xml');
    $feedId = (int)$_GET['id'];
    $feed = $feedsXml->xpath("//feed[@id='$feedId']")[0];

    if (!$feed) {
        die("<p style='color: red;'>Flux non trouvé.</p><a href='index.php'>Retour à l'accueil</a>");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $feed['category'] = htmlspecialchars($_POST['category']);
        $feed->name = htmlspecialchars($_POST['name']);
        $feed->url = htmlspecialchars($_POST['url']);
        $feed->display = htmlspecialchars($_POST['display']);

        if (strpos($_POST['display'], 'recent') !== false) {
            if (isset($feed->max_age_days)) {
                $feed->max_age_days = (int)$_POST['max_age_days'];
            } else {
                $feed->addChild('max_age_days', (int)$_POST['max_age_days']);
            }
        } else {
            unset($feed->max_age_days);
        }

        // Sauvegarder le fichier XML
        $feedsXml->asXML('feeds.xml');

        header("Location: index.php");
        exit;
    }

    $name = (string)$feed->name;
    $url = (string)$feed->url;
    $category = (string)$feed['category'];
    $display = (string)$feed->display;
    $maxAgeDays = isset($feed->max_age_days) ? (int)$feed->max_age_days : 2;
    ?>

    <form action="edit_feed.php?id=<?php echo $feedId; ?>" method="post">
        <label for="name">Nom du flux :</label>
        <input type="text" id="name" name="name" value="<?php echo $name; ?>" required>

        <label for="url">URL du flux (RSS ou Atom) :</label>
        <input type="url" id="url" name="url" value="<?php echo $url; ?>" required>

        <label for="category">Catégorie (onglet) :</label>
        <input type="text" id="category" name="category" value="<?php echo $category; ?>" required>

        <label for="display">Option d'affichage :</label>
        <select id="display" name="display" required>
            <option value="10" <?php echo ($display === '10') ? 'selected' : ''; ?>>10 derniers articles complets</option>
            <option value="10_titles" <?php echo ($display === '10_titles') ? 'selected' : ''; ?>>Uniquement les 10 derniers titres</option>
            <option value="recent" <?php echo ($display === 'recent') ? 'selected' : ''; ?>>Articles complets des 2 derniers jours</option>
            <option value="recent_titles" <?php echo ($display === 'recent_titles') ? 'selected' : ''; ?>>Uniquement les titres des 2 derniers jours</option>
            <option value="latest" <?php echo ($display === 'latest') ? 'selected' : ''; ?>>Article le plus récent + 9 titres</option>
        </select>

        <label for="max_age_days">Nombre de jours max (si option "récente") :</label>
        <input type="number" id="max_age_days" name="max_age_days" min="1" value="<?php echo $maxAgeDays; ?>">

		<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Enregistrer</button>
		<button class="btn btn-secondary"><a href="index.php"><i class="fas fa-times"></i> Annuler</a></button>    
	</form>
</body>
</html>