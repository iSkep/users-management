<?php

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "softsprint";
$table = "users";

// Config
$roles = [
    1 => 'Admin',
    2 => 'User',
];

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

function executeQuery($query, $types = "", $params = [], $fetchAll = false)
{
    global $conn;
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    if ($fetchAll) {
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    return $affectedRows;
}

function formatUsers($users)
{
    global $roles;

    foreach ($users as &$user) {
        $user['status'] = (bool) $user['status'];
        // $user['role'] = $roles[$user['role_id']] ?? 'Unknown';
        // unset($user['role_id']);
    }

    return $users;
}

function loadInitialData()
{
    global $roles, $table;
    $users = executeQuery("SELECT * FROM $table", "", [], true);
    $users = formatUsers($users);

    return [
        'users' => $users,
        'roles' => $roles,
    ];
}
