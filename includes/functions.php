<?php
require_once __DIR__ . '/db.php';

/** Échappement HTML pour tout ce qui vient de la base ou d'un formulaire. */
function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Les 5 statuts imposés par le cahier des charges, dans l'ordre du traitement. */
function report_statuses()
{
    return [
        'recu' => 'Reçu',
        'valide' => 'Validé',
        'en_cours' => 'En cours',
        'resolu' => 'Résolu',
        'rejete' => 'Rejeté',
    ];
}

function status_label($status)
{
    $labels = report_statuses();
    return $labels[$status] ?? $status;
}

/** Couleur utilisée pour la pastille de statut et les marqueurs de la carte. */
function status_color($status)
{
    $colors = [
        'recu' => '#64748b',
        'valide' => '#0ea5a4',
        'en_cours' => '#f59e0b',
        'resolu' => '#22c55e',
        'rejete' => '#ef4444',
    ];
    return $colors[$status] ?? '#64748b';
}

/**
 * Un agent peut-il modifier ce signalement ?
 * Oui s'il lui est affecté, ou s'il appartient au service affecté.
 * L'administrateur peut tout modifier.
 */
function can_manage_report($report)
{
    $user = current_user();
    if ($user === null) {
        return false;
    }
    if ($user['role'] === 'admin') {
        return true;
    }
    if ($user['role'] === 'agent') {
        if (!empty($report['agent_id']) && (int) $report['agent_id'] === (int) $user['id']) {
            return true;
        }
        if (!empty($report['service_id']) && !empty($user['service_id'])
            && (int) $report['service_id'] === (int) $user['service_id']) {
            return true;
        }
    }
    return false;
}

/**
 * Crée une notification en base pour l'auteur d'un signalement.
 * C'est le mécanisme de "notification des changements de statut" :
 * à chaque changement de statut, l'auteur reçoit un message lisible
 * dans notifications.php (et la pastille de l'en-tête).
 */
function notify_user($user_id, $report_id, $message)
{
    $stmt = db()->prepare(
        'INSERT INTO notifications (user_id, report_id, message) VALUES (?, ?, ?)'
    );
    $stmt->execute([$user_id, $report_id, $message]);
}

function unread_notifications_count($user_id)
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0'
    );
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}

/**
 * Change le statut d'un signalement, en respectant la règle de gestion :
 * un rejet exige un motif. Le motif est effacé si le signalement
 * repasse ensuite à un autre statut.
 * Enregistre l'événement dans l'historique et notifie l'auteur.
 */
function change_report_status($report, $new_status, $changed_by, $rejection_reason = null)
{
    $statuses = report_statuses();
    if (!isset($statuses[$new_status])) {
        return 'Statut invalide.';
    }
    if ($new_status === 'rejete' && trim((string) $rejection_reason) === '') {
        return 'Un signalement rejeté doit comporter un motif de rejet.';
    }

    $pdo = db();
    $reason = $new_status === 'rejete' ? trim((string) $rejection_reason) : null;
    $stmt = $pdo->prepare(
        'UPDATE reports SET status = ?, rejection_reason = ?, updated_at = NOW() WHERE id = ?'
    );
    $stmt->execute([$new_status, $reason, $report['id']]);

    $stmt = $pdo->prepare(
        'INSERT INTO status_history (report_id, old_status, new_status, changed_by, reason)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$report['id'], $report['status'], $new_status, $changed_by, $reason]);

    if ((int) $report['user_id'] !== (int) $changed_by) {
        $message = 'Votre signalement #' . $report['id'] . ' est passé au statut « '
            . status_label($new_status) . ' ».'
            . ($reason ? ' Motif : ' . $reason : '');
        notify_user((int) $report['user_id'], (int) $report['id'], $message);
    }
    return null;
}

/**
 * Valide et déplace une photographie envoyée.
 * Retourne le chemin relatif (ex: uploads/reports/abc123.jpg) ou null
 * si aucun fichier n'a été envoyé. En cas d'erreur, remplit $error.
 */
function upload_photo($field, $subfolder, &$error = null)
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Erreur lors de l’envoi du fichier.';
        return false;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        $error = 'La photographie ne doit pas dépasser 5 Mo.';
        return false;
    }
    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $info = @getimagesize($file['tmp_name']);
    if (!isset($allowed[$extension]) || $info === false || $allowed[$extension] !== $info['mime']) {
        $error = 'Seules les images JPG, PNG et WebP sont acceptées.';
        return false;
    }

    $dir = __DIR__ . '/../uploads/' . $subfolder;
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $filename = bin2hex(random_bytes(12)) . '.' . $extension;
    $destination = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $error = 'Impossible d’enregistrer la photographie.';
        return false;
    }
    return 'uploads/' . $subfolder . '/' . $filename;
}

/** Liste des quartiers déjà utilisés, pour la saisie semi-automatique. */
function existing_quartiers()
{
    $stmt = db()->query('SELECT DISTINCT quartier FROM reports WHERE quartier <> "" ORDER BY quartier');
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
