<?php
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "db_name";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    sendResponse(false, 500, "Database connection failed");
}

// Determine the action from the request
$action = $_REQUEST['action'] ?? '';

// Function to send a JSON response
function sendResponse($status, $code = null, $message = null, $data = [])
{
    echo json_encode([
        "status" => $status,
        "error" => $status ? null : ["code" => $code, "message" => $message],
    ] + $data);
    exit;
}

// Function to prepare and execute SQL queries
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

    $stmt->close();
    return true;
}

// Handling different actions
switch ($action) {
    case 'get_users': // Retrieve the list of users
        $users = executeQuery("SELECT * FROM users", "", [], true);

        // Convert status to boolean
        foreach ($users as &$user) {
            $user['status'] = (bool) $user['status'];
        }

        sendResponse(true, null, null, ["users" => $users]);
        break;

    case 'get_user': // Retrieve information about a specific user
        $id = $_GET['id'] ?? null;
        if (!$id) {
            sendResponse(false, 400, "User ID required");
        }

        $user = executeQuery("SELECT * FROM users WHERE id=?", "i", [$id], true);
        if (!$user) {
            sendResponse(false, 404, "User not found");
        }

        $user[0]['status'] = (bool) $user[0]['status'];
        sendResponse(true, null, null, ["user" => $user[0]]);
        break;

    case 'save_user': // Create or update a user
        $id = $_POST['id'] ?? null;
        $first_name = trim($_POST['firstName'] ?? '');
        $last_name = trim($_POST['lastName'] ?? '');
        $status = isset($_POST['status']) && $_POST['status'] === 'true' ? 1 : 0;
        $role = $_POST['role'] ?? 'user';

        if (!$first_name || !$last_name) {
            sendResponse(false, 400, "Fields cannot be empty");
        }

        if ($id) {
            // Update an existing user
            executeQuery("UPDATE users SET first_name=?, last_name=?, status=?, role=? WHERE id=?", "ssisi", [$first_name, $last_name, $status, $role, $id]);
        } else {
            // Create a new user
            executeQuery("INSERT INTO users (first_name, last_name, status, role) VALUES (?, ?, ?, ?)", "ssis", [$first_name, $last_name, $status, $role]);
            $id = $conn->insert_id;
        }

        $user = executeQuery("SELECT * FROM users WHERE id=?", "i", [$id], true)[0];
        $user['status'] = (bool) $user['status'];

        sendResponse(true, null, null, ["user" => $user]);
        break;

    case 'delete_user': // Delete a user
        $id = $_POST['id'] ?? null;
        if (!$id) {
            sendResponse(false, 400, "User ID required");
        }

        executeQuery("DELETE FROM users WHERE id=?", "i", [$id]);
        sendResponse(true);
        break;

    case 'bulk_action': // Bulk operations (activate, deactivate, delete)
        $ids = $_POST['ids'] ?? [];
        $operation = $_POST['operation'] ?? '';

        if (empty($ids) || !is_array($ids)) {
            sendResponse(false, 400, "No users selected");
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        switch ($operation) {
            case 'activate':
                executeQuery("UPDATE users SET status = 1 WHERE id IN ($placeholders)", $types, $ids);
                break;
            case 'deactivate':
                executeQuery("UPDATE users SET status = 0 WHERE id IN ($placeholders)", $types, $ids);
                break;
            case 'delete':
                executeQuery("DELETE FROM users WHERE id IN ($placeholders)", $types, $ids);
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
