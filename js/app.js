$(document).ready(function () {
    const backendUrl = 'actions.php';

    // Save User
    $('[data-save-btn]').click(function () {
        const formData = {
            id: $('#user-id').val(),
            firstName: $('#first-name').val(),
            lastName: $('#last-name').val(),
            status: $('#status').is(':checked'),
            role_id: $('#role').val(),
        };

        $.ajax({
            url: backendUrl,
            type: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
                action: 'save_user',
                ...formData,
            }),
            success: function (response) {
                if (response.status) {
                    formData.role = $('#role').find(':selected').text();

                    $('[data-user-modal]').modal('hide');
                    $('.error-message').hide();

                    // case new user
                    if (!formData.id) {
                        formData.id = response.user_id;

                        addUserRow(formData);
                        updateCheckAllState();
                    } else {
                        // case edit user
                        updateUserRow(formData);
                    }
                } else {
                    $('.error-message')
                        .text('Error: ' + response.error.message)
                        .show();
                }
            },
        });
    });

    // Add/Edit User
    $(document).on('click', '[data-show-user-modal]', function () {
        const isEdit = $(this).is('[data-edit-user]');
        const modalTitle = isEdit ? 'Edit User' : 'Add User';
        const row = isEdit ? $(this).closest('tr') : null;
        const userId = isEdit ? row.data('user-id') : '';
        const firstName = isEdit ? row.find('.user-name__first').text() : '';
        const lastName = isEdit ? row.find('.user-name__last').text() : '';
        const status = isEdit ? row.find('.status').hasClass('active') : false;
        const role = isEdit ? row.find('.user-role').text() : '';

        // Fill the form with user data or reset for new user
        $('#user-id').val(userId);
        $('#first-name').val(firstName);
        $('#last-name').val(lastName);
        $('#status').prop('checked', status);
        $('#role option').each(function () {
            if ($(this).text() === role) {
                $(this).prop('selected', true);
            }
        });

        // if (!isEdit) {
        //     $('#role').val('');
        // }

        $('.modal-title').text(modalTitle);
        $('[data-user-modal]').modal('show');
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
                    contentType: 'application/json',
                    dataType: 'json',
                    data: JSON.stringify({ action: 'delete_user', id: userId }),
                    success: function (response) {
                        if (response.status) {
                            row.remove();
                            updateCheckAllState();
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
    function addUserRow(data) {
        const newRow = `
            <tr data-user-id="${data.id}">
                <td class="users-table__cell">
                    <input type="checkbox" class="user-checkbox" value="${data.id}">
                </td>
                <td class="users-table__cell user-name">
                    <span class="user-name__first">${data.firstName}</span>
                    <span class="user-name__last">${data.lastName}</span>
                </td>
                <td class="users-table__cell user-status">
                    <span class="status ${data.status ? 'active' : ''}">
                        <i class="bi bi-circle-fill"></i>
                    </span>
                </td>
                <td class="users-table__cell user-role">${data.role}</td>
                <td class="users-table__cell">
                    <button class="btn btn-sm btn-outline-warning" data-id="${
                        data.id
                    }" data-show-user-modal data-edit-user>
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" data-id="${data.id}" data-delete-user>
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#users-list').append(newRow);
    }

    function updateUserRow(data) {
        const row = $(`[data-user-id="${data.id}"]`);
        const statusCell = row.find('.status');

        row.find('.user-name__first').text(data.firstName);
        row.find('.user-name__last').text(data.lastName);

        if (data.status) {
            statusCell.addClass('active');
        } else {
            statusCell.removeClass('active');
        }

        row.find('.user-role').text(data.role);
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
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({ action: 'bulk_action', operation: operation, ids: ids }),
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
        $('#role option:selected').prop('selected', false);
    });
});
