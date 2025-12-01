<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- Mobile Toggle Button -->
<button class="btn btn-link d-md-none position-fixed top-0 start-0 mt-2 ms-2 z-3" id="sidebarToggle" type="button">
    <i class="bi bi-list fs-2 text-primary"></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay d-md-none" id="sidebarOverlay"></div>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main id="main-content" class="col-md-10 ms-sm-auto px-md-4">
            <?php echo $content; ?>
        </main>
    </div>
</div>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailModalLabel">Send Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="emailForm">
                    <input type="hidden" id="emailUserId" name="user_id">

                    <div class="mb-3">
                        <label for="emailTemplate" class="form-label">Email Template (Optional)</label>
                        <select class="form-select" id="emailTemplate" onchange="loadTemplate()">
                            <option value="">Select a template...</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="emailSubject" class="form-label">Subject *</label>
                        <input type="text" class="form-control" id="emailSubject" name="subject" required>
                    </div>

                    <div class="mb-3">
                        <label for="emailBody" class="form-label">Email Body *</label>
                        <textarea class="form-control" id="emailBody" name="body" rows="8" required></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="saveAsTemplate" name="save_template">
                            <label class="form-check-label" for="saveAsTemplate">
                                Save as template for future use
                            </label>
                        </div>
                        <div class="mt-2" id="templateNameDiv" style="display: none;">
                            <input type="text" class="form-control" id="templateName" name="template_name" placeholder="Template name">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendEmail(false)">Send</button>
                <button type="button" class="btn btn-success" onclick="sendEmail(true)">Save and Send</button>
            </div>
        </div>
    </div>
</div>

<style>
.sidebar-sticky {
    position: relative;
    top: 0;
    height: calc(100vh - 48px);
    padding-top: .5rem;
    overflow-x: hidden;
    overflow-y: auto;
}

#main-content {
    margin-left: 0;
    padding-top: 20px;
}

.sidebar .nav-link {
    font-weight: 500;
    color: #333;
}

.sidebar .nav-link.active {
    color: #007bff;
}

.sidebar .nav-link:hover {
    color: #007bff;
}

@media (max-width: 768px) {
    #sidebar {
        position: fixed;
        top: 0;
        bottom: 0;
        left: -250px;
        z-index: 100;
        width: 250px;
        background: white;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        transition: left 0.3s ease;
        padding: 48px 0 0;
    }
    body.sidebar-active #sidebar {
        left: 0;
    }
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 99;
        display: none;
    }
    body.sidebar-active .sidebar-overlay {
        display: block;
    }
    #main-content {
        margin-left: 0;
    }
}
</style>

<script>
// Global email functions
let currentUserId = null;
let currentUserName = null;
let currentUserEmail = null;

// Initialize email modal when document is ready
$(document).ready(function() {
    loadEmailTemplates();
});

// Load email templates into dropdown
function loadEmailTemplates() {
    $.ajax({
        url: 'ajax/email_templates.php',
        type: 'GET',
        dataType: 'json',
        success: function(templates) {
            const select = $('#emailTemplate');
            select.empty();
            select.append('<option value="">Select a template...</option>');

            templates.forEach(function(template) {
                select.append(`<option value="${template.id}">${template.name}</option>`);
            });
        },
        error: function() {
            console.error('Failed to load email templates');
        }
    });
}

// Load selected template content
function loadTemplate() {
    const templateId = $('#emailTemplate').val();
    if (!templateId) {
        $('#emailSubject').val('');
        $('#emailBody').val('');
        return;
    }

    $.ajax({
        url: 'ajax/email_templates.php',
        type: 'GET',
        data: { id: templateId },
        dataType: 'json',
        success: function(template) {
            $('#emailSubject').val(template.subject);
            $('#emailBody').val(template.body);
        },
        error: function() {
            console.error('Failed to load template');
        }
    });
}

// Open email modal for specific user
function openEmailModal(userId, userName, userEmail) {
    currentUserId = userId;
    currentUserName = userName;
    currentUserEmail = userEmail;

    $('#emailUserId').val(userId);
    $('#emailSubject').val('');
    $('#emailBody').val('');
    $('#emailTemplate').val('');
    $('#saveAsTemplate').prop('checked', false);
    $('#templateNameDiv').hide();
    $('#templateName').val('');

    const modal = new bootstrap.Modal(document.getElementById('emailModal'));
    modal.show();
}

// Handle save as template checkbox
$('#saveAsTemplate').change(function() {
    if ($(this).is(':checked')) {
        $('#templateNameDiv').show();
        $('#templateName').attr('required', true);
    } else {
        $('#templateNameDiv').hide();
        $('#templateName').removeAttr('required');
    }
});

// Send email function
function sendEmail(saveTemplate = true) {
    const form = document.getElementById('emailForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    formData.append('action', 'send_email');
    formData.append('save_template', saveTemplate ? '1' : '0');

    // Show loading
    Swal.fire({
        title: 'Sending...',
        text: 'Please wait while we send the email',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: 'ajax/send_email.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('emailModal'));
                modal.hide();

                // Show success message
                Swal.fire({
                    title: 'Email Sent!',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Reload templates if we saved one
                    if (saveTemplate) {
                        loadEmailTemplates();
                    }

                    // Reload page
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: response.message || 'Failed to send email.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function() {
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while sending the email.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>