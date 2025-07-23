<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login');
    exit;
}

require_once __DIR__ . '/../../config.php';

// Handle prompt save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prompt_content'])) {
    $prompt_content = $_POST['prompt_content'];
    
    try {
        $pdo = getDB();
        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE system_prompts SET is_active = false WHERE prompt_key LIKE 'main%' AND is_active = true");
            $stmt->execute();
            
            $unique_key = 'main_' . time() . '_' . uniqid();
            $stmt = $pdo->prepare("INSERT INTO system_prompts (prompt_key, prompt_name, prompt_content, is_active) VALUES (:key, :name, :content, true)");
            $stmt->execute([
                'key' => $unique_key,
                'name' => 'Main System Prompt',
                'content' => $prompt_content
            ]);
            
            $success = "Prompt saved successfully!";
        } else {
            $error = "Database connection failed";
        }
    } catch (Exception $e) {
        $error = "Error saving prompt: " . $e->getMessage();
    }
}

// Handle prompt restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_id'])) {
    try {
        $pdo = getDB();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM system_prompts WHERE id = :id");
            $stmt->execute(['id' => $_POST['restore_id']]);
            $promptToRestore = $stmt->fetch();
            
            if ($promptToRestore) {
                $stmt = $pdo->prepare("UPDATE system_prompts SET is_active = false WHERE prompt_key LIKE 'main%' AND is_active = true");
                $stmt->execute();
                
                $unique_key = 'main_' . time() . '_' . uniqid();
                $stmt = $pdo->prepare("INSERT INTO system_prompts (prompt_key, prompt_name, prompt_content, is_active) VALUES (:key, :name, :content, true)");
                $stmt->execute([
                    'key' => $unique_key,
                    'name' => 'Main System Prompt',
                    'content' => $promptToRestore['prompt_content']
                ]);
                
                $success = "Prompt restored successfully!";
            }
        } else {
            $error = "Database connection failed";
        }
    } catch (Exception $e) {
        $error = "Error restoring prompt: " . $e->getMessage();
    }
}

// Get current active prompt
$currentPrompt = null;
try {
    $pdo = getDB();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM system_prompts WHERE prompt_key LIKE 'main%' AND is_active = true ORDER BY created_at DESC LIMIT 1");
        $stmt->execute();
        $currentPrompt = $stmt->fetch();
    }
} catch (Exception $e) {
    // Table might not exist yet
}

// Get prompt history
$promptHistory = [];
try {
    $pdo = getDB();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT id, prompt_name, created_at, is_active, prompt_content, LENGTH(prompt_content) as content_length FROM system_prompts WHERE prompt_key LIKE 'main%' ORDER BY created_at DESC LIMIT 20");
        $stmt->execute();
        $promptHistory = $stmt->fetchAll();
    }
} catch (Exception $e) {
    // Table might not exist yet
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Settings - AI Assistant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts Inter - NOTE: Consider adding font files locally or use system fonts -->
    <!-- <link href="assets/fonts/inter.css" rel="stylesheet"> -->
    <link href="../../assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../../assets/css/common.css" rel="stylesheet">
    <style>
        /* Page-specific styles */

        .settings-card {
            background: var(--geist-card);
            border: 1px solid var(--geist-border);
            border-radius: var(--geist-radius);
            padding: 32px;
            margin-bottom: 24px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 16px 0;
            color: var(--geist-foreground);
        }

        .card-subtitle {
            color: var(--geist-muted);
            font-size: 14px;
            margin: 0 0 24px 0;
        }

        .form-control.prompt-textarea {
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
            min-height: 200px;
            line-height: 1.5;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background: rgba(0, 168, 84, 0.1);
            color: var(--geist-success);
        }

        .status-inactive {
            background: var(--geist-background);
            color: var(--geist-muted);
        }

        /* Modal adjustments */
        .modal-content {
            max-width: 900px;
        }

        .prompt-content {
            background: var(--geist-background);
            border: 1px solid var(--geist-border);
            border-radius: var(--geist-radius);
            padding: 16px;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.5;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
            color: var(--geist-foreground);
        }

        @media (max-width: 768px) {
            .settings-card {
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body>
    <?php include '../nav.php'; ?>

    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">Settings</h1>
            <p class="page-subtitle">Configure system prompts and AI behavior</p>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <div class="alert-content"><?php echo htmlspecialchars($success); ?></div>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <div class="alert-content"><?php echo htmlspecialchars($error); ?></div>
        </div>
        <?php endif; ?>

        <div class="settings-card">
            <h2 class="card-title">System Prompt</h2>
            <p class="card-subtitle">
                Configure the core instructions that guide the AI assistant's behavior and responses.
                <?php if ($currentPrompt): ?>
                    Last updated: <?php echo date('M j, Y g:i A', strtotime($currentPrompt['created_at'])); ?>
                <?php endif; ?>
            </p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="prompt_content" class="form-label">Prompt Content</label>
                    <textarea 
                        id="prompt_content" 
                        name="prompt_content" 
                        class="form-control prompt-textarea" 
                        rows="12" 
                        placeholder="Enter the system prompt that will guide AI Assistant's behavior..."
                        required><?php echo htmlspecialchars($currentPrompt['prompt_content'] ?? 'You are an AI Assistant designed to help users with their questions and tasks. You provide comprehensive information and assistance on a wide range of topics.'); ?></textarea>
                </div>
                <button type="submit" class="btn-primary btn-large">
                    Save Prompt
                </button>
            </form>
        </div>

        <?php if (!empty($promptHistory)): ?>
        <div class="settings-card">
            <h2 class="card-title">Prompt History</h2>
            <p class="card-subtitle">View and restore previous versions of your system prompt</p>
            
            <div class="table-container">
                <table id="promptTable" class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Created</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($promptHistory as $prompt): ?>
                        <tr>
                            <td><?php echo $prompt['id']; ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($prompt['created_at'])); ?></td>
                            <td><?php echo number_format($prompt['content_length']); ?> chars</td>
                            <td>
                                <span class="status-badge <?php echo $prompt['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $prompt['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-secondary" onclick="viewPrompt(<?php echo $prompt['id']; ?>)">View</button>
                                <?php if (!$prompt['is_active']): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="restore_id" value="<?php echo $prompt['id']; ?>">
                                    <button type="submit" class="btn-warning">Restore</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="settings-card">
            <div class="empty-state">
                <h3 class="empty-state-title">No prompt history</h3>
                <p class="empty-state-text">Save your first prompt to start building a history</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal -->
    <div class="modal" id="promptModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Prompt Content</h3>
                <button type="button" class="btn-secondary" id="modal-close">
                    Close
                </button>
            </div>
            <div class="modal-body">
                <div id="modalContent" class="prompt-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="modal-cancel">Close</button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/jquery-3.7.0.min.js"></script>
    <script src="../../assets/js/jquery.dataTables.min.js"></script>
    <script src="../../assets/js/dataTables.bootstrap5.min.js"></script>

    <script>
        const promptData = <?php echo json_encode(array_map(function($p) {
            return [
                'id' => $p['id'],
                'content' => $p['prompt_content']
            ];
        }, $promptHistory)); ?>;

        const modal = document.getElementById('promptModal');
        const closeBtn = document.getElementById('modal-close');
        const cancelBtn = document.getElementById('modal-cancel');

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        $(document).ready(function() {
            <?php if (!empty($promptHistory)): ?>
            $('#promptTable').DataTable({
                scrollY: '50vh',
                scrollCollapse: true,
                paging: false,
                info: true,
                searching: true,
                ordering: true,
                order: [[1, 'desc']], // Sort by created date
                columnDefs: [
                    { orderable: false, targets: [4] }
                ],
                language: {
                    emptyTable: "No prompt history found",
                    zeroRecords: "No matching prompts found",
                    info: "Showing _TOTAL_ prompts",
                    infoEmpty: "No prompts",
                    infoFiltered: "(filtered from _MAX_ total)",
                    search: "Search prompts:"
                }
            });
            <?php endif; ?>
        });

        function viewPrompt(id) {
            const prompt = promptData.find(p => p.id == id);
            if (!prompt) return;
            
            document.getElementById('modalContent').textContent = prompt.content;
            showModal();
        }

        function showModal() {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        // Auto-dismiss alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('alert-success')) {
                    setTimeout(() => {
                        alert.style.opacity = '0';
                        setTimeout(() => alert.remove(), 300);
                    }, 3000);
                }
            });
        });
    </script>
</body>
</html>