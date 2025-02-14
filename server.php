<?php
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "db_name";
$table = "users";

// Config
$roles = [
    1 => 'Admin',
    2 => 'User',
];

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    sendResponse(false, 500, "Database connection failed");
}

// Determine the action from the request
$action = $_REQUEST['action'] ?? '';

// Handling different actions
switch ($action) {
    case 'get_users': // Retrieve the list of users
        $users = executeQuery("SELECT * FROM $table", "", [], true);
        $users = formatUsers($users);
        sendResponse(true, null, null, ["users" => $users, "roles" => $roles]);
        break;

    case 'get_user': // Retrieve information about a specific user
        $id = $_GET['id'] ?? null;
        if (!$id) {
            sendResponse(false, 400, "User ID required");
        }

        $user = executeQuery("SELECT * FROM $table WHERE id=?", "i", [$id], true);
        if (!$user) {
            sendResponse(false, 404, "User not found", ["not_found_id" => (int) $id]);
        }

        $user = formatUsers($user);
        sendResponse(true, null, null, ["user" => $user[0]]);
        break;

    case 'save_user': // Create or update a user
        $id = $_POST['id'] ?? null;
        $first_name = trim($_POST['firstName'] ?? '');
        $last_name = trim($_POST['lastName'] ?? '');
        $status = isset($_POST['status']) && $_POST['status'] === 'true' ? 1 : 0;
        $role_id = $_POST['role_id'] ?? 2;

        if (!array_key_exists($role_id, $roles)) {
            sendResponse(false, 400, "Invalid role ID");
        }

        if (!$first_name || !$last_name) {
            sendResponse(false, 400, "Fields cannot be empty");
        }

        if ($id) {
            // Update an existing user
            executeQuery("UPDATE $table SET first_name=?, last_name=?, status=?, role_id=? WHERE id= ?", "ssiii", [$first_name, $last_name, $status, $role_id, $id]);
        } else {
            // Create a new user
            executeQuery("INSERT INTO $table (first_name, last_name, status, role_id) VALUES (?, ?, ?, ?)", "ssii", [$first_name, $last_name, $status, $role_id]);
            $id = $conn->insert_id;
        }

        $user = executeQuery("SELECT * FROM $table WHERE id=?", "i", [$id], true)[0];
        $user = formatUsers([$user])[0];

        sendResponse(true, null, null, ["user" => $user]);
        break;

    case 'delete_user': // Delete a user
        $id = $_POST['id'] ?? null;

        if (!$id) {
            sendResponse(false, 400, "User ID required");
        }

        $affectedRows = executeQuery("DELETE FROM $table WHERE id=?", "i", [$id]);

        if ($affectedRows === 0) {
            sendResponse(false, 400, "User not found", ["not_found_id" => (int) $id]);
        }

        sendResponse(true);
        break;

    case 'bulk_action': // Bulk operations (activate, deactivate, delete)
        $ids = $_POST['ids'] ?? [];
        $operation = $_POST['operation'] ?? '';

        // Check if no users are selected
        if (empty($ids) || !is_array($ids)) {
            sendResponse(false, 400, "No users selected");
        }

        // Check if the provided IDs exist in the database
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $result = executeQuery("SELECT id FROM $table WHERE id IN ($placeholders)", $types, $ids, true);

        // Find the IDs that do not exist in the database
        $existingIds = array_column($result, 'id');
        $notFoundIds = array_diff($ids, $existingIds);

        // If there are any non-existing IDs
        if (!empty($notFoundIds)) {
            sendResponse(false, 404, "Users not found", ["not_found_ids" => array_map('intval', array_values($notFoundIds))]);
        }

        switch ($operation) {
            case 'activate':
                executeQuery("UPDATE $table SET status = 1 WHERE id IN ($placeholders)", $types, $ids);
                break;
            case 'deactivate':
                executeQuery("UPDATE $table SET status = 0 WHERE id IN ($placeholders)", $types, $ids);
                break;
            case 'delete':
                executeQuery("DELETE FROM $table WHERE id IN ($placeholders)", $types, $ids);
                break;
            default:
                sendResponse(false, 400, "Invalid operation");
        }

        sendResponse(true);
        break;

    default:
        sendResponse(false, 400, "Invalid action");
}

// Close the database connection
$conn->close();

// Send a JSON response
function sendResponse($status, $code = null, $message = null, $data = [])
{
    echo json_encode([
        "status" => $status,
        "error" => $status ? null : ["code" => $code, "message" => $message],
    ] + $data);
    exit;
}

// Prepare and execute SQL queries
function executeQuery($query, $types = "", $params = [], $fetchAll = false)
{
    global $conn;
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        sendResponse(false, 500, "Database error: " . $conn->error);
    }

    // Bind parameters if they exist
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    // Fetch data if needed
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

// Transform users data 
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
