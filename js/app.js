$(document).ready(function () {
    const backendUrl = 'server.php';
    let roles = {};

    // Initial Load
    initDataLoad();

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
                role_id: $('#role').val(),
            },
            success: function (response) {
                if (response.status) {
                    $('[data-user-modal]').modal('hide');
                    $('.error-message').hide();

                    // case new user
                    if (!$('#user-id').val()) {
                        addUserRow(response.user);
                        updateCheckAllState();
                    } else {
                        // case edit user
                        updateUserRow(response.user);
                    }
                } else {
                    $('.error-message')
                        .text('Error: ' + response.error.message)
                        .show();
                }
            },
        });
    });

    // Edit User
    $(document).on('click', '[data-edit-user]', function () {
        const userId = $(this).closest('tr').data('user-id');
        const userName = $(this).closest('tr').find('.user-name').text();

        $.get(`${backendUrl}?action=get_user&id=${userId}`, function (response) {
            if (response.status) {
                $('#user-id').val(response.user.id);
                $('#first-name').val(response.user.first_name);
                $('#last-name').val(response.user.last_name);
                $('#status').prop('checked', response.user.status);
                $('#role').val(response.user.role_id);
                $('[data-user-modal]').modal('show');
            } else {
                if (response.not_found_id) {
                    showMessage('Error', response.error.message + `: <span class="fw-bold">${userName}</span>`);
                } else {
                    showMessage('Error', response.error.message);
                }
            }
        });
    });

    // Delete User
    $(document).on('click', '[data-delete-user]', function () {
        const userId = $(this).closest('tr').data('user-id');
        const userName = $(this).closest('tr').find('.user-name').text();
        const row = $(this).closest('tr');

        row.addClass('removing');

        showConfirm(`Delete user <strong>${userName}</strong>?`, null, function (confirmed) {
            if (confirmed) {
                $.ajax({
                    url: backendUrl,
                    type: 'POST',
                    data: { action: 'delete_user', id: userId },
                    success: function (response) {
                        if (response.status) {
                            row.remove();
                        } else {
                            showMessage('Error', response.error.message);
                            row.removeClass('removing');
                        }
                    },
                });
            } else {
                row.removeClass('removing');
            }
        });
    });

    // Bulk Actions
    $('[data-apply-action]').click(function () {
        const parent = $(this).closest('[data-select-container]');
        const selectedOperation = parent.find('[data-select]').val();
        const selectedIds = $('.user-checkbox:checked')
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
            const usersToDelete = $('.user-checkbox:checked')
                .map(function () {
                    return $(this).closest('tr').find('.user-name').text();
                })
                .get();

            $('.user-checkbox:checked').closest('tr').addClass('removing');

            showConfirm('Are you sure you want to delete selected users?', usersToDelete, function (confirmed) {
                if (confirmed) {
                    executeBulkAction(selectedOperation, selectedIds);
                } else {
                    $('.user-checkbox:checked').closest('tr').removeClass('removing');
                }
            });
        } else {
            executeBulkAction(selectedOperation, selectedIds);
        }
    });

    // Sync Selects
    // $(document).ready(function () {
    //     $('[data-first-select], [data-second-select]').on('change', function () {
    //         let selectedValue = $(this).val();
    //         $('[data-first-select], [data-second-select]').val(selectedValue);
    //     });
    // });

    // Checkboxes
    $('[data-check-all]').change(onCheckAllChange);
    $('.table').change(function (e) {
        if (e.target && e.target.classList.contains('user-checkbox')) {
            updateCheckAllState();
        }
    });

    // =====================================================================================================================
    // Functions and Handlers
    // =====================================================================================================================

    // User Management Functions
    function initDataLoad() {
        $.ajax({
            url: backendUrl,
            type: 'GET',
            data: { action: 'get_users' },
            success: function (response) {
                roles = response.roles;
                let rows = '';

                response.users.forEach((user) => {
                    rows += `
                    <tr data-user-id="${user.id}">
                        <td class="users-table__cell">
                            <input type="checkbox" class="user-checkbox" value="${user.id}">
                        </td>
                        <td class="users-table__cell user-name">${user.first_name} ${user.last_name}</td>
                        <td class="users-table__cell user-status">
                            <span class="status ${user.status ? 'active' : ''}">
                                <i class="bi bi-circle-fill"></i>
                            </span>
                        </td>
                        <td class="users-table__cell user-role">${roles[user.role_id]}</td>
                        <td class="users-table__cell">
                            <button class="btn btn-sm btn-outline-warning" data-id="${user.id}" data-edit-user>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" data-id="${user.id}" data-delete-user>
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    `;
                });

                let rolesOptions = '';
                Object.entries(roles).forEach(([roleId, roleName]) => {
                    rolesOptions += `<option value="${roleId}">${roleName}</option>`;
                });

                $('#users-list').html(rows);
                $('#role').html(rolesOptions);
            },
        });
    }

    function addUserRow(user) {
        const newRow = `
            <tr data-user-id="${user.id}">
                <td class="users-table__cell">
                    <input type="checkbox" class="user-checkbox" value="${user.id}">
                </td>
                <td class="users-table__cell user-name">${user.first_name} ${user.last_name}</td>
                <td class="users-table__cell user-status">
                    <span class="status ${user.status ? 'active' : ''}">
                        <i class="bi bi-circle-fill"></i>
                    </span>
                </td>
                <td class="users-table__cell user-role">${roles[user.role_id]}</td>
                <td class="users-table__cell">
                    <button class="btn btn-sm btn-outline-warning" data-id="${user.id}" data-edit-user>
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" data-id="${user.id}" data-delete-user>
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#users-list').append(newRow);
    }

    function updateUserRow(user) {
        const row = $(`[data-user-id="${user.id}"]`);
        const statusCell = row.find('.status');

        row.find('.user-name').text(user.first_name + ' ' + user.last_name);

        if (user.status) {
            statusCell.addClass('active');
        } else {
            statusCell.removeClass('active');
        }

        row.find('.user-role').text(user.role);
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
                    ids.forEach((id) => {
                        const row = $(`tr[data-user-id="${id}"]`);

                        if (operation === 'delete') {
                            row.remove();
                        } else if (operation === 'activate' || operation === 'deactivate') {
                            const statusCell = row.find('.status');

                            if (operation === 'activate') {
                                statusCell.addClass('active');
                            } else {
                                statusCell.removeClass('active');
                            }
                        }
                    });
                } else {
                    if (response.not_found_ids && response.not_found_ids.length > 0) {
                        const notFoundIds = response.not_found_ids;
                        const notFoundNames = [];

                        notFoundIds.forEach(function (id) {
                            const row = $(`tr[data-user-id="${id}"]`);
                            const userName = row.find('.user-name').text();

                            if (userName) {
                                notFoundNames.push(userName);
                            }
                        });

                        let userListHtml = '<ul class="users-list fw-bold">';

                        notFoundNames.forEach(function (name) {
                            userListHtml += `<li>${name}</li>`;
                        });
                        userListHtml += '</ul>';

                        showMessage('Error', response.error.message + userListHtml);
                    } else {
                        showMessage('Error', response.error.message);
                    }
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
    function showConfirm(message, users, callback) {
        let userListHtml = '';

        if (users && users.length > 0) {
            userListHtml = '<ul class="users-list fw-bold">';
            users.forEach((user) => {
                userListHtml += `<li>${user}</li>`;
            });
            userListHtml += '</ul>';
        }

        $('[data-confirm-body]').html(message + userListHtml);
        $('[data-confirm-modal]').modal('show');

        $('[data-confirm-action]')
            .off('click')
            .on('click', function () {
                $('[data-confirm-modal]').modal('hide');
                callback(true);
            });

        $('[data-confirm-modal]').on('hidden.bs.modal', function () {
            callback(false);
        });
    }

    // BS modal aria-hidden fix https://github.com/twbs/bootstrap/issues/41005
    $(window).on('hide.bs.modal', function () {
        if ($(document.activeElement)[0] instanceof HTMLElement) {
            $(document.activeElement).blur();
        }
    });

    // Hide error
    $('[data-user-modal]').on('hidden.bs.modal', function () {
        $('.error-message').hide();
    });
});
