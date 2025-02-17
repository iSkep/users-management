<?php
require_once 'server.php';

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

switch ($action) {
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
            $existingUser = executeQuery("SELECT id FROM $table WHERE id=?", "i", [$id], true);

            if (!$existingUser) {
                sendResponse(false, 404, "User not found", ["not_found_id" => (int) $id]);
            }
            // Update an existing user
            executeQuery("UPDATE $table SET first_name=?, last_name=?, status=?, role_id=? WHERE id= ?", "ssiii", [$first_name, $last_name, $status, $role_id, $id]);
        } else {
            // Create a new user
            executeQuery("INSERT INTO $table (first_name, last_name, status, role_id) VALUES (?, ?, ?, ?)", "ssii", [$first_name, $last_name, $status, $role_id]);
            $id = $conn->insert_id;
            sendResponse(true, null, null, ["user_id" => $id]);
        }

        // $user = executeQuery("SELECT * FROM $table WHERE id=?", "i", [$id], true)[0];
        // $user = formatUsers([$user])[0];

        sendResponse(true);
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

function sendResponse($status, $code = null, $message = null, $data = [])
{
    echo json_encode([
        "status" => $status,
        "error" => $status ? null : ["code" => $code, "message" => $message],
    ] + $data);
    exit;
}
