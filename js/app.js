$(document).ready(function () {
    const backendUrl = 'server.php';

    // Initial Load
    loadUsers();

    // Add User
    $('[data-add-user]').click(function () {
        $('#user-id').val('');
        $('[data-form]')[0].reset();
        $('[data-user-modal]').modal('show');
    });

    // Save User
    $('[data-save-btn]').click(function () {
        $.ajax({
            url: backendUrl,
            type: 'POST',
            data: {
                action: 'save_user',
                id: $('#user-id').val(),
                firstName: $('#first-name').val(),
                lastName: $('#last-name').val(),
                status: $('#status').is(':checked'),
                role: $('#role').val(),
            },
            success: function (response) {
                if (response.status) {
                    $('[data-user-modal]').modal('hide');
                    loadUsers();
                } else {
                    showMessage('Error', response.error.message);
                }
            },
        });
    });

    // Edit User
    $(document).on('click', '[data-edit-user]', function () {
        let userId = $(this).data('id');

        $.get(`${backendUrl}?action=get_user&id=${userId}`, function (response) {
            if (response.status) {
                $('#user-id').val(response.user.id);
                $('#first-name').val(response.user.first_name);
                $('#last-name').val(response.user.last_name);
                $('#status').prop('checked', response.user.status);
                $('#role').val(response.user.role);
                $('[data-user-modal]').modal('show');
            } else {
                showMessage('Error', response.error.message);
            }
        });
    });

    // Delete User
    $(document).on('click', '[data-delete-user]', function () {
        let userId = $(this).data('id');

        showConfirm('Are you sure?', function () {
            $.ajax({
                url: backendUrl,
                type: 'POST',
                data: { action: 'delete_user', id: userId },
                success: function (response) {
                    if (response.status) {
                        loadUsers();
                    } else {
                        showMessage('Error', response.error.message);
                    }
                },
            });
        });
    });

    // Bulk Actions
    $('[data-apply-action]').click(function () {
        let selectedOperation = $('[data-first-select]').val();
        let selectedIds = $('.user-checkbox:checked')
            .map(function () {
                return this.value;
            })
            .get();

        if (selectedIds.length === 0) {
            showMessage('Error', 'No users selected!');
            return;
        }

        if (!selectedOperation) {
            showMessage('Error', 'Please select an action!');
            return;
        }

        if (selectedOperation === 'delete') {
            showConfirm('Are you sure you want to delete selected users?', function () {
                executeBulkAction(selectedOperation, selectedIds);
            });
        } else {
            executeBulkAction(selectedOperation, selectedIds);
        }
    });

    // Sync Selects
    $(document).ready(function () {
        $('[data-first-select], [data-second-select]').on('change', function () {
            let selectedValue = $(this).val();
            $('[data-first-select], [data-second-select]').val(selectedValue);
        });
    });

    // Checkboxes
    $('[data-check-all]').change(onCheckAllChange);
    $('.table').change(function (e) {
        if (e.target && e.target.classList.contains('user-checkbox')) {
            updateCheckAllState();
        }
    });

    // =====================================================================================================================
    // Functions and Handlers

    // Load Users
    function loadUsers() {
        $.ajax({
            url: backendUrl,
            type: 'GET',
            data: { action: 'get_users' },
            success: function (response) {
                let rows = '';
                response.users.forEach((user) => {
                    rows += `
                    <tr>
                        <td class="users-table__cell"><input type="checkbox" class="user-checkbox" value="${
                            user.id
                        }"></td>
                        <td class="users-table__cell">${user.first_name} ${user.last_name}</td>
                        <td class="users-table__cell">${
                            user.status
                                ? '<span class="text-success">●</span>'
                                : '<span class="text-secondary">●</span>'
                        }</td>
                        <td class="users-table__cell">${user.role}</td>
                        <td class="users-table__cell">
                            <button class="btn btn-sm btn-outline-warning" data-id="${
                                user.id
                            }" data-edit-user>✎</button>
                            <button class="btn btn-sm btn-outline-danger" data-id="${
                                user.id
                            }" data-delete-user>🗑</button>
                        </td>
                    </tr>
                    `;
                });
                $('#users-list').html(rows);
                updateCheckAllState();
            },
        });
    }

    // Check All Checkbox
    function onCheckAllChange() {
        $('.user-checkbox').prop('checked', this.checked);
    }

    function updateCheckAllState() {
        const isAllChecked = $('.user-checkbox').length === $('.user-checkbox:checked').length;

        $('[data-check-all]').prop('checked', isAllChecked);
    }

    // Execute Bulk Action
    function executeBulkAction(operation, ids) {
        $.ajax({
            url: backendUrl,
            type: 'POST',
            data: { action: 'bulk_action', operation: operation, ids: ids },
            success: function (response) {
                if (response.status) {
                    loadUsers();
                } else {
                    showMessage('Error', response.error.message);
                }
            },
        });
    }

    // Message Modal
    function showMessage(title, message) {
        $('[data-message-title]').text(title);
        $('[data-message-body]').html(message);
        $('[data-message-modal]').modal('show');
    }

    // Confirm Modal
    function showConfirm(message, callback) {
        $('[data-confirm-body]').text(message);
        $('[data-confirm-modal]').modal('show');

        $('[data-confirm-action]')
            .off('click')
            .on('click', function () {
                $('[data-confirm-modal]').modal('hide');
                callback();
            });
    }
});
