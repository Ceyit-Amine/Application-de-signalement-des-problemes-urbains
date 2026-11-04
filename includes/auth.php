<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Utilisateur connecté (tableau) ou null. */
function current_user()
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in()
{
    return current_user() !== null;
}

function has_role($role)
{
    $user = current_user();
    return $user !== null && $user['role'] === $role;
}

function is_admin()
{
    return has_role('admin');
}

function is_agent()
{
    return has_role('agent');
}

/** Redirige vers la connexion si l'utilisateur n'est pas connecté. */
function require_login()
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/** Redirige vers l'accueil si l'utilisateur n'a pas l'un des rôles donnés. */
function require_role($roles)
{
    require_login();
    if (!in_array(current_user()['role'], (array) $roles, true)) {
        header('Location: index.php');
        exit;
    }
}

/** Enregistre l'utilisateur en session après connexion / inscription. */
function login_user($user)
{
    // On ne garde en session que ce dont l'affichage a besoin.
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'nom' => $user['nom'],
        'email' => $user['email'],
        'role' => $user['role'],
        'service_id' => $user['service_id'] ? (int) $user['service_id'] : null,
    ];
}
