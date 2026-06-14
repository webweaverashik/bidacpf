document.addEventListener('DOMContentLoaded', function () {
    // Elements
    const createBtn = document.getElementById('btn-create-backup');
    const modalEl = document.getElementById('kt_modal_create_backup');
    const modalForm = document.getElementById('kt_modal_create_backup_form');
    const submitBtn = document.getElementById('kt_modal_create_backup_submit');
    
    let modal;
    if (modalEl) {
        modal = new bootstrap.Modal(modalEl);
    }

    // Interactive Radio Buttons (Add active class to parent label)
    const radioButtons = modalForm?.querySelectorAll('input[name="backup_type"]');
    if (radioButtons) {
        radioButtons.forEach(radio => {
            radio.addEventListener('change', function() {
                // Remove active classes from all labels
                radioButtons.forEach(r => {
                    const label = r.closest('label');
                    if (label) {
                        label.classList.remove('border-primary', 'bg-light-primary');
                        label.classList.remove('border-success', 'bg-light-success');
                        label.classList.remove('border-info', 'bg-light-info');
                        label.classList.add('border-gray-300');
                    }
                });

                // Add active class to selected label
                const label = this.closest('label');
                if (label) {
                    label.classList.remove('border-gray-300');
                    if (this.value === 'database') {
                        label.classList.add('border-primary', 'bg-light-primary');
                    } else if (this.value === 'files') {
                        label.classList.add('border-success', 'bg-light-success');
                    } else if (this.value === 'both') {
                        label.classList.add('border-info', 'bg-light-info');
                    }
                }
            });
        });
        
        // Trigger change on the checked one initially
        const checked = modalForm.querySelector('input[name="backup_type"]:checked');
        if (checked) {
            checked.dispatchEvent(new Event('change'));
        }
    }

    // Open Modal
    if (createBtn && modal) {
        createBtn.addEventListener('click', function (e) {
            e.preventDefault();
            modal.show();
        });
    }

    // Handle Form Submit
    if (modalForm && submitBtn) {
        modalForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(modalForm);
            const backupType = formData.get('backup_type');

            // Close Modal Immediately
            modal.hide();

            // Show SweetAlert Preloader
            let loadingMessage = 'Please wait while the backup is being created.';
            if (backupType === 'both') {
                loadingMessage = 'Creating database and files backup. This may take a few minutes...';
            } else if (backupType === 'files') {
                loadingMessage = 'Creating files backup. This may take a few minutes...';
            }

            Swal.fire({
                title: 'Creating Backup...',
                html: loadingMessage,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(backupRoutes.create, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ backup_type: backupType })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateTable(data.backups);
                    updateStats(data.total_size, data.last_backup, data.backups.length);
                    
                    // Check if there are created backups to offer download
                    let downloadHtml = '';
                    if (data.created_backups && Object.keys(data.created_backups).length > 0) {
                        downloadHtml = '<div class="mt-5"><p class="fw-semibold mb-3">Download your backups:</p>';
                        
                        for (const [type, backup] of Object.entries(data.created_backups)) {
                            const typeLabel = type === 'database' ? 'Database' : 'Files';
                            const btnClass = type === 'database' ? 'btn-light-primary' : 'btn-light-success';
                            const icon = type === 'database' ? 'ki-data' : 'ki-folder';
                            
                            downloadHtml += `
                                <a href="${backup.download_url}" class="btn ${btnClass} btn-sm me-2 mb-2">
                                    <i class="ki-outline ${icon} fs-4 me-1"></i>
                                    ${typeLabel} (${backup.size_formatted})
                                </a>
                            `;
                        }
                        
                        downloadHtml += '</div>';
                    }
                    
                    // Show warning if there were partial errors
                    let warningHtml = '';
                    if (data.errors && data.errors.length > 0) {
                        warningHtml = '<div class="alert alert-warning mt-4"><strong>Warning:</strong> ' + data.errors.join('<br>') + '</div>';
                    }
                    
                    Swal.fire({
                        title: 'Success!',
                        html: data.message + warningHtml + downloadHtml,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Backup creation failed.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        },
                        buttonsStyling: false
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An unexpected error occurred.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'btn btn-danger'
                    },
                    buttonsStyling: false
                });
            });
        });
    }

    // Delete Backup Buttons (Event Delegation)
    document.addEventListener('click', function (e) {
        const deleteBtn = e.target.closest('.btn-delete-backup');
        if (deleteBtn) {
            const filename = deleteBtn.getAttribute('data-filename');
            const type = deleteBtn.getAttribute('data-type') || 'database';
            const typeLabel = type === 'database' ? 'Database' : 'Files';
            
            Swal.fire({
                title: 'Delete Backup?',
                html: `<p>Are you sure you want to delete this ${typeLabel.toLowerCase()} backup:</p><p class="fw-bold text-danger">${filename}</p><p class="text-muted">This action cannot be undone.</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-light'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Deleting...',
                        html: 'Please wait while the backup is being deleted.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    const deleteUrl = `${backupRoutes.destroy}/${filename}?type=${type}`;
                    
                    fetch(deleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateTable(data.backups);
                            updateStats(data.total_size, data.last_backup, data.backups.length);
                            
                            Swal.fire({
                                title: 'Deleted!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonText: 'OK',
                                customClass: {
                                    confirmButton: 'btn btn-primary'
                                },
                                buttonsStyling: false
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'Delete failed.',
                                icon: 'error',
                                confirmButtonText: 'OK',
                                customClass: {
                                    confirmButton: 'btn btn-danger'
                                },
                                buttonsStyling: false
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'An unexpected error occurred.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'btn btn-danger'
                            },
                            buttonsStyling: false
                        });
                    });
                }
            });
        }
    });

    // Update Table with New Data
    function updateTable(backups) {
        const tbody = document.getElementById('backup-table-body');
        if (!tbody) return;

        if (!backups || backups.length === 0) {
            tbody.innerHTML = `
                <tr id="no-backups-row">
                    <td colspan="6" class="text-center py-10">
                        <i class="ki-outline ki-file-deleted fs-3x text-gray-400 mb-5"></i>
                        <p class="text-gray-500 fs-5 mb-0">No backups found</p>
                        <p class="text-gray-400 fs-7">Click "Create Backup" to generate your first backup</p>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        backups.forEach((backup, index) => {
            html += `
                <tr data-filename="${backup.filename}" data-type="${backup.type}">
                    <td class="ps-4">${index + 1}</td>
                    <td>
                        <span class="badge ${backup.type_badge}">
                            ${backup.type_label}
                        </span>
                    </td>
                    <td>
                        <i class="ki-outline ki-file-added fs-4 text-primary me-2"></i>
                        ${backup.filename}
                    </td>
                    <td>${backup.size_formatted}</td>
                    <td>${backup.date_formatted}</td>
                    <td class="pe-4 text-end">
                        <a href="${backup.download_url}" class="btn btn-sm btn-light-success me-2" title="Download">
                            <i class="ki-outline ki-file-down fs-4"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-light-danger btn-delete-backup" 
                                data-filename="${backup.filename}" 
                                data-type="${backup.type}"
                                title="Delete">
                            <i class="ki-outline ki-trash fs-4"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        tbody.innerHTML = html;
    }

    // Update Stats
    function updateStats(totalSize, lastBackup, totalCount) {
        const statsTotal = document.getElementById('stats-total');
        const statsSize = document.getElementById('stats-size');
        const statsLast = document.getElementById('stats-last');

        if (statsTotal) statsTotal.textContent = totalCount;
        if (statsSize) statsSize.textContent = totalSize;
        if (statsLast) statsLast.textContent = lastBackup;
    }
});