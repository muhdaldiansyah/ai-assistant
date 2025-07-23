<?php
require_once __DIR__ . '/../auth_check.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Documents - AI Assistant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts Inter - NOTE: Consider adding font files locally or use system fonts -->
    <!-- <link href="assets/fonts/inter.css" rel="stylesheet"> -->
    <link href="../../assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../../assets/css/common.css" rel="stylesheet">
    <style>
        /* Page-specific styles */

        .chunks-badge {
            display: inline-flex;
            align-items: center;
            background: var(--geist-background);
            color: var(--geist-foreground);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        /* Modal adjustments for wider content */
        #editModal .modal-content {
            max-width: 900px; /* Increased from 600px to 900px */
            width: 95%; /* Use more screen width on larger screens */
        }

        .form-control.textarea {
            resize: vertical;
            min-height: 400px; /* Increased from 200px */
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
            font-size: 14px; /* Slightly larger font */
            line-height: 1.6; /* Better line spacing */
        }

        /* Modal body scrolling for long content */
        #editModal .modal-body {
            max-height: calc(80vh - 180px); /* Account for header and footer */
            overflow-y: auto;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            #editModal .modal-content {
                max-width: 95%;
                width: 95%;
            }
            
            .form-control.textarea {
                min-height: 300px;
            }
        }

    </style>
</head>
<body>
    <?php include '../nav.php'; ?>

    <div class="main-container">
        <div class="page-header">
            <div class="page-header-content">
                <div class="page-header-text">
                    <h1 class="page-title">Documents</h1>
                    <p class="page-subtitle">Manage your knowledge base and document library</p>
                </div>
                <div class="header-actions">
                    <a href="upload.php" class="btn-primary">
                        Add Document
                    </a>
                </div>
            </div>
        </div>

        <div id="error-alert" class="alert d-none">
            <span id="error-text"></span>
        </div>

        <div class="stats-info" id="stats-info">
            Loading documents...
        </div>

        <div class="table-container">
            <table id="documentsTable" class="table">
                <thead>
                    <tr>
                        <th>Document Name</th>
                        <th>Source File</th>
                        <th>Program Title</th>
                        <th>Chunks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>

        <div id="empty-state" class="empty-state d-none">
            <h3 class="empty-state-title">No documents yet</h3>
            <p class="empty-state-text">Upload your first document to get started with your knowledge base</p>
            <a href="upload.php" class="btn-primary">
                Upload Document
            </a>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Document</h3>
                <button type="button" class="btn-secondary" id="modal-close">
                    Close
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="modalFilename" class="form-label">Document Name</label>
                    <input type="text" class="form-control" id="modalFilename" placeholder="Enter document name">
                </div>
                <div class="form-group">
                    <label for="modalContent" class="form-label">Content</label>
                    <textarea class="form-control textarea" id="modalContent" placeholder="Document content..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="modal-cancel">Cancel</button>
                <button type="button" class="btn-primary" id="saveBtn">
                    <span id="save-text">Save Changes</span>
                </button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/jquery-3.7.0.min.js"></script>
    <script src="../../assets/js/jquery.dataTables.min.js"></script>
    <script src="../../assets/js/dataTables.bootstrap5.min.js"></script>
    <script src="/assets/js/common.js"></script>

    <script>
        let table;
        let currentDocument = null;

        // Initialize
        $(document).ready(function() {
            loadDocuments();
            initializeModal();
        });

        function initializeModal() {
            const modal = document.getElementById('editModal');
            const closeBtn = document.getElementById('modal-close');
            const cancelBtn = document.getElementById('modal-cancel');
            const saveBtn = document.getElementById('saveBtn');

            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            saveBtn.addEventListener('click', saveDocument);

            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
        }

        function loadDocuments() {
            table = $('#documentsTable').DataTable({
                ajax: {
                    url: 'api/list_doc.php?op=list',
                    dataSrc: function(json) {
                        updateStats(json);
                        if (json.length === 0) {
                            showEmptyState();
                        } else {
                            hideEmptyState();
                        }
                        return json;
                    },
                    error: function(xhr, error, thrown) {
                        showError('Failed to load documents: ' + thrown);
                        showEmptyState();
                    }
                },
                columns: [
                    { 
                        data: 'filename',
                        render: function(data, type, row) {
                            return data || row.program_title || 'Unknown';
                        }
                    },
                    { 
                        data: 'source_file',
                        render: function(data) {
                            return data || '';
                        }
                    },
                    { 
                        data: 'program_title',
                        render: function(data) {
                            return data || '';
                        }
                    },
                    { 
                        data: 'chunks',
                        render: function(data) {
                            return `<span class="chunks-badge">${data}</span>`;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            const docName = row.filename || row.program_title || 'Unknown';
                            return `
                                <button class="btn-secondary" onclick="editDocument('${escapeHtml(docName)}')">Edit</button>
                                <button class="btn-danger" onclick="deleteDocument('${escapeHtml(docName)}')">Delete</button>
                            `;
                        },
                        orderable: false
                    }
                ],
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                info: true,
                searching: true,
                ordering: true,
                order: [[0, 'asc']],
                language: {
                    emptyTable: "No documents found",
                    zeroRecords: "No matching documents found",
                    info: "Showing _TOTAL_ documents",
                    infoEmpty: "No documents",
                    infoFiltered: "(filtered from _MAX_ total)",
                    search: "Search documents:"
                }
            });
        }

        function updateStats(data) {
            const totalChunks = data.reduce((sum, doc) => sum + doc.chunks, 0);
            $('#stats-info').text(`${data.length} documents • ${totalChunks} total chunks`);
        }

        function showError(message) {
            $('#error-alert').removeClass('d-none');
            $('#error-text').text(message);
        }

        function showEmptyState() {
            $('#empty-state').removeClass('d-none');
            $('.table-container').addClass('d-none');
        }

        function hideEmptyState() {
            $('#empty-state').addClass('d-none');
            $('.table-container').removeClass('d-none');
        }

        async function editDocument(docName) {
            try {
                currentDocument = docName;
                
                const response = await fetch(`api/list_doc.php?op=get&doc=${encodeURIComponent(docName)}`);
                const data = await response.json();
                
                if (data.error) throw new Error(data.error);
                
                document.getElementById('modalFilename').value = docName;
                document.getElementById('modalContent').value = data.content || '';
                
                showModal();
                
            } catch (error) {
                showError('Failed to load document: ' + error.message);
            }
        }

        async function deleteDocument(docName) {
            if (!confirm(`Are you sure you want to delete "${docName}"?\n\nThis action cannot be undone.`)) {
                return;
            }

            try {
                const response = await fetch(`api/list_doc.php?op=delete&doc=${encodeURIComponent(docName)}`);
                const data = await response.json();
                
                if (data.error) throw new Error(data.error);
                
                table.ajax.reload();
                
            } catch (error) {
                showError('Failed to delete document: ' + error.message);
            }
        }

        async function saveDocument() {
            if (!currentDocument) return;
            
            const newFilename = document.getElementById('modalFilename').value.trim();
            if (!newFilename) {
                alert('Document name cannot be empty');
                return;
            }
            
            const saveBtn = document.getElementById('saveBtn');
            const saveText = document.getElementById('save-text');
            
            try {
                saveBtn.disabled = true;
                saveText.innerHTML = '<span class="loading-spinner"></span> Saving...';
                
                const response = await fetch(`api/list_doc.php?op=save&doc=${encodeURIComponent(currentDocument)}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        filename: newFilename,
                        content: document.getElementById('modalContent').value
                    })
                });
                
                const result = await response.json();
                if (result.error) throw new Error(result.error);
                
                closeModal();
                table.ajax.reload();
                
            } catch (error) {
                showError('Failed to save document: ' + error.message);
            } finally {
                saveBtn.disabled = false;
                saveText.textContent = 'Save Changes';
            }
        }

        function showModal() {
            const modal = document.getElementById('editModal');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
            currentDocument = null;
        }
    </script>
</body>
</html>