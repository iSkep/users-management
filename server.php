<?php

require_once "core/Database.php";

// Load config
$config = require 'config.php';

$db = new Database($config['host'], $config['username'], $config['password'], $config['dbname']);
$table = "users";
$roles = [
    1 => 'Admin',
    2 => 'User',
];

function formatUsers(array $users): array
{
    global $roles;

    foreach ($users as &$user) {
        $user['status'] = (bool) $user['status'];
        // $user['role'] = $roles[$user['role_id']] ?? 'Unknown';
        // unset($user['role_id']);
    }

    return $users;
}

function loadInitialData(Database $db, string $table, array $roles): array
{
    $users = $db->executeQuery("SELECT * FROM $table", "", [], true);
    $users = formatUsers($users, $roles);

    return [
        'users' => $users,
        'roles' => $roles,
    ];
}
