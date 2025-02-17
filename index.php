<?php

require_once 'server.php';

$data = loadInitialData();

$users = $data['users'] ?? [];
$roles = $data['roles'] ?? [];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="css/libs/bootstrap.min.css">
    <link rel="stylesheet" href="css/libs/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <div class="container pt-3">
        <h1 class="text-center">Users</h1>
        <div class="d-flex justify-content-between mb-3">
            <button class="btn btn-primary me-2" type="button" data-add-user>Add User</button>
            <div class="d-flex" data-select-container>
                <select class="form-select me-2" data-select>
                    <option value="">- Please Select -</option>
                    <option value="activate">Set active</option>
                    <option value="deactivate">Set not active</option>
                    <option value="delete">Delete</option>
                </select>
                <button class="btn btn-secondary" type="button" data-apply-action>OK</button>
            </div>
        </div>
        <table class="users-table table table-bordered table-light">
            <thead>
                <tr>
                    <th class="users-table__header users-table__header_checkbox" scope="col">
                        <input type="checkbox" data-check-all>
                    </th>
                    <th class="users-table__header users-table__header_name" scope="col">Name</th>
                    <th class="users-table__header users-table__header_status" scope="col">Status</th>
                    <th class="users-table__header users-table__header_role" scope="col">Role</th>
                    <th class="users-table__header users-table__header_options" scope="col">Options</th>
                </tr>
            </thead>
            <tbody id="users-list">
                <?php foreach ($users as $user): ?>
                    <tr data-user-id="<?= $user['id'] ?>">
                        <td class="users-table__cell">
                            <input type="checkbox" class="user-checkbox" value="<?= $user['id'] ?>">
                        </td>
                        <td class="users-table__cell user-name">
                            <span class="user-name__first"><?= $user['first_name'] ?></span>
                            <span class="user-name__last"><?= $user['last_name'] ?></span>
                        </td>
                        <td class="users-table__cell user-status">
                            <span class="status <?= $user['status'] ? 'active' : '' ?>">
                                <i class="bi bi-circle-fill"></i>
                            </span>
                        </td>
                        <td class="users-table__cell user-role"><?= $roles[$user['role_id']] ?></td>
                        <td class="users-table__cell">
                            <button class="btn btn-sm btn-outline-warning" data-id="<?= $user['id'] ?>" data-edit-user>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" data-id="<?= $user['id'] ?>" data-delete-user>
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="d-flex justify-content-between mb-3">
            <button class="btn btn-primary me-2" type="button" data-add-user>Add User</button>
            <div class="d-flex" data-select-container>
                <select class="form-select me-2" data-select>
                    <option value="">- Please Select -</option>
                    <option value="activate">Set active</option>
                    <option value="deactivate">Set not active</option>
                    <option value="delete">Delete</option>
                </select>
                <button class="btn btn-secondary" type="button" data-apply-action>OK</button>
            </div>
        </div>
    </div>
    <!-- User modal -->
    <div class="modal fade" tabindex="-1" aria-hidden="true" data-user-modal>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="user-form" data-form>
                        <input id="user-id" type="hidden">
                        <div class="mb-3">
                            <label for="first-name" class="form-label">First Name</label>
                            <input id="first-name" class="form-control" type="text">
                        </div>
                        <div class="mb-3">
                            <label for="last-name" class="form-label">Last Name</label>
                            <input id="last-name" class="form-control" type="text">
                        </div>
                        <div class="mb-3 form-check form-switch">
                            <input id="status" class="form-check-input" type="checkbox" role="switch">
                            <label for="status" class="form-check-label">Status</label>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select id="role" class="form-select">
                                <?php foreach ($roles as $roleId => $roleName): ?>
                                    <option value="<?= $roleId ?>"><?= $roleName ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <div class="error-message"></div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" data-save-btn>Save</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Message modal -->
    <div class="modal fade" tabindex="-1" aria-hidden="true" data-message-modal>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" data-message-title>Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body" data-message-body></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Delete confirmation modal -->
    <div class="modal fade" tabindex="-1" aria-hidden="true" data-confirm-modal>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body" data-confirm-body>Are you sure?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" data-confirm-action>Yes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/libs/jquery-3.7.1.min.js"></script>
    <script src="js/libs/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
</body>

</html>