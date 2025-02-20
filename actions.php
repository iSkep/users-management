<?php

require_once 'server.php';
require_once 'core/Database.php';

// Load config
$config = require 'config.php';

$db = new Database($config['host'], $config['username'], $config['password'], $config['dbname']);

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

switch ($action) {
    case 'save_user': // Create or update a user
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        $firstName = htmlspecialchars(trim($data['firstName'] ?? ''), ENT_QUOTES, 'UTF-8');
        $lastName = htmlspecialchars(trim($data['lastName'] ?? ''), ENT_QUOTES, 'UTF-8');
        $status = filter_var($data['status'] ?? 'false', FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $roleId = filter_var($data['role_id'] ?? null, FILTER_VALIDATE_INT);

        if (!array_key_exists($roleId, $roles)) {
            sendResponse(false, 400, "Invalid role ID");
        }

        if (!$firstName || !$lastName) {
            sendResponse(false, 400, "Fields cannot be empty");
        }

        if ($id) {
            $existingUser = $db->executeQuery("SELECT id FROM $table WHERE id=?", "i", [$id], true);

            if (!$existingUser) {
                sendResponse(false, 404, "User not found", ["not_found_id" => (int) $id]);
            }
            // Update an existing user
            $db->executeQuery("UPDATE $table SET first_name=?, last_name=?, status=?, role_id=? WHERE id= ?", "ssiii", [$firstName, $lastName, $status, $roleId, $id]);
        } else {
            // Create a new user
            $db->executeQuery("INSERT INTO $table (first_name, last_name, status, role_id) VALUES (?, ?, ?, ?)", "ssii", [$firstName, $lastName, $status, $roleId]);
            $id = $db->getLastInsertId();
            sendResponse(true, null, null, ["user_id" => $id]);
        }

        sendResponse(true);
        break;

    case 'delete_user': // Delete a user
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id) {
            sendResponse(false, 400, "User ID required");
        }

        $affectedRows = $db->executeQuery("DELETE FROM $table WHERE id=?", "i", [$id]);

        if ($affectedRows === 0) {
            sendResponse(false, 400, "User not found", ["not_found_id" => (int) $id]);
        }

        sendResponse(true);
        break;

    case 'bulk_action': // Bulk operations (activate, deactivate, delete)
        $ids = array_map(function ($id) {
            return filter_var($id, FILTER_VALIDATE_INT);
        }, $data['ids'] ?? []);
        $operation = htmlspecialchars($data['operation'] ?? '', ENT_QUOTES, 'UTF-8');

        // Check if no users are selected
        if (empty($ids) || !is_array($ids)) {
            sendResponse(false, 400, "No users selected");
        }

        // Check if the provided IDs exist in the database
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $result = $db->executeQuery("SELECT id FROM $table WHERE id IN ($placeholders)", $types, $ids, true);

        // Find the IDs that do not exist in the database
        $existingIds = array_column($result, 'id');
        $notFoundIds = array_diff($ids, $existingIds);

        // If there are any non-existing IDs
        if (!empty($notFoundIds)) {
            sendResponse(false, 404, "Users not found", ["not_found_ids" => array_map('intval', array_values($notFoundIds))]);
        }

        switch ($operation) {
            case 'activate':
                $db->executeQuery("UPDATE $table SET status = 1 WHERE id IN ($placeholders)", $types, $ids);
                break;
            case 'deactivate':
                $db->executeQuery("UPDATE $table SET status = 0 WHERE id IN ($placeholders)", $types, $ids);
                break;
            case 'delete':
                $db->executeQuery("DELETE FROM $table WHERE id IN ($placeholders)", $types, $ids);
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
$db->close();

function sendResponse(bool $status, int $code = null, string $message = null, array $data = []): void
{
    echo json_encode([
        "status" => $status,
        "error" => $status ? null : ["code" => $code, "message" => $message],
    ] + $data);
    exit;
}
