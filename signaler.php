<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$error = '';
$success_id = null;
$categories = db()->query('SELECT * FROM categories ORDER BY nom')->fetchAll();
$quartiers = existing_quartiers();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $quartier = trim($_POST['quartier'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $latitude = $_POST['latitude'] !== '' ? (float) $_POST['latitude'] : null;
    $longitude = $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;

    if ($titre === '' || $description === '' || $category_id <= 0 || $quartier === '') {
        $error = 'Le titre, la description, la catégorie et le quartier sont obligatoires.';
    } else {
        $photo = upload_photo('photo', 'reports', $error);
        if ($photo !== false) {
            $stmt = db()->prepare(
                'INSERT INTO reports
                 (user_id, category_id, titre, description, quartier, adresse, latitude, longitude, photo, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "recu")'
            );
            $stmt->execute([
                current_user()['id'], $category_id, $titre, $description,
                $quartier, $adresse ?: null, $latitude, $longitude, $photo,
            ]);
            $success_id = (int) db()->lastInsertId();
        }
    }
}

$page_title = 'Signaler un problème';
require __DIR__ . '/includes/header.php';
?>
<h1>Signaler un problème dans l’espace public</h1>
<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
<?php if ($success_id): ?>
    <p class="alert alert-success">Votre signalement a été enregistré avec le statut « Reçu ».
        <a href="signalement.php?id=<?= $success_id ?>">Voir le signalement</a></p>
<?php endif; ?>

<div class="card">
    <form method="post" enctype="multipart/form-data">
        <label>Titre du problème <input type="text" name="titre" required
            placeholder="Ex : Nid-de-poule rue principale"></label>
        <label>Catégorie
            <select name="category_id" required>
                <option value="">— Choisir —</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>"><?= e($cat['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Description <textarea name="description" required
            placeholder="Décrivez le problème le plus précisément possible…"></textarea></label>
        <label>Quartier
            <input type="text" name="quartier" list="quartiers" required
                   placeholder="Ex : Centre-ville">
            <datalist id="quartiers">
                <?php foreach ($quartiers as $q): ?><option value="<?= e($q) ?>"><?php endforeach; ?>
                <option value="Centre-ville"><option value="Médina">
                <option value="Quartier Nord"><option value="Quartier Sud">
            </datalist>
        </label>
        <label>Adresse (facultative) <input type="text" name="adresse"
            placeholder="Rue, repère…"></label>
        <label>Photographie (JPG, PNG ou WebP, 5 Mo max)
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>

        <label>Localisation sur la carte — cliquez sur la carte pour placer le problème</label>
        <div id="picker-map" class="map map-small"></div>
        <p><button type="button" id="geolocate-btn" class="btn-secondary">📍 Utiliser ma position actuelle</button></p>
        <label>Latitude <input type="number" step="any" id="latitude" name="latitude"></label>
        <label>Longitude <input type="number" step="any" id="longitude" name="longitude"></label>

        <button type="submit">Envoyer le signalement</button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
