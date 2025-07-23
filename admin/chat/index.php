<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Chat History - AI Assistant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts Inter - NOTE: Consider adding font files locally or use system fonts -->
    <!-- <link href="assets/fonts/inter.css" rel="stylesheet"> -->
    <link href="../../assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../../assets/css/common.css" rel="stylesheet">
    <style>
        /* Page-specific styles */

        .table tbody tr.clickable-row {
            cursor: pointer;
        }

        .thread-id {
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
            font-size: 12px;
            color: var(--geist-muted);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            background: var(--geist-background);
            color: var(--geist-foreground);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-info {
            background: rgba(0, 112, 243, 0.1);
            color: var(--geist-primary);
        }

        .badge-secondary {
            background: var(--geist-background);
            color: var(--geist-muted);
        }

        .user-info {
            font-size: 13px;
            line-height: 1.3;
        }

        .user-name {
            font-weight: 600;
            color: var(--geist-foreground);
        }

        .user-email {
            color: var(--geist-muted);
            font-size: 12px;
        }

        .user-phone {
            color: var(--geist-muted);
            font-size: 12px;
        }

        .user-nationality {
            background: rgba(0, 112, 243, 0.1);
            color: var(--geist-primary);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
            margin-top: 2px;
        }

        .thread-detail {
            background: var(--geist-card);
            border: 1px solid var(--geist-border);
            border-radius: var(--geist-radius);
            overflow: hidden;
        }

        .thread-detail-header {
            padding: 20px;
            border-bottom: 1px solid var(--geist-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .thread-detail-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
            color: var(--geist-foreground);
        }

        .messages-container {
            max-height: 60vh;
            overflow-y: auto;
            background: var(--geist-background);
        }

        .message {
            padding: 20px;
            border-bottom: 1px solid var(--geist-border);
            background: var(--geist-card);
            margin: 0;
        }

        .message:last-child {
            border-bottom: none;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .message-role {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .message-role.user {
            color: var(--geist-primary);
        }

        .message-role.assistant {
            color: var(--geist-success);
        }

        .message-time {
            font-size: 12px;
            color: var(--geist-muted);
        }

        .message-content {
            color: var(--geist-foreground);
            white-space: pre-wrap;
            line-height: 1.6;
            font-size: 14px;
        }



        /* Mobile adjustments */
        @media (max-width: 768px) {
            .thread-detail-header {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .user-info {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <?php include '../nav.php'; ?>

    <div class="main-container">
        <div id="threadList">
            <div class="page-header">
                <div class="page-header-content">
                    <div class="page-header-text">
                        <h1 class="page-title">Chat History</h1>
                        <p class="page-subtitle">View and continue your previous conversations</p>
                    </div>
                    <div class="header-actions">
                        <a href="../../app" class="btn-primary">
                            New Chat
                        </a>
                    </div>
                </div>
            </div>

            <div id="error-alert" class="alert d-none">
                <span id="error-text"></span>
            </div>

            <div class="stats-info" id="stats-info">
                Loading chat history...
            </div>

            <div class="table-container" id="threads-table-container">
                <table id="threadsTable" class="table">
                    <thead>
                        <tr>
                            <th>Thread ID</th>
                            <th>Title</th>
                            <th>User Information</th>
                            <th>Messages</th>
                            <th>Created</th>
                            <th>Last Activity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $pdo = getDB();
                            if ($pdo) {
                                $stmt = $pdo->prepare("
                                    SELECT t.id, t.title, t.created_at, t.updated_at, t.user_id,
                                           t.full_name, t.email, t.phone_number, t.nationality,
                                           COUNT(m.id) as message_count,
                                           MAX(m.created_at) as last_message_at
                                    FROM threads t
                                    LEFT JOIN messages m ON t.id = m.thread_id
                                    GROUP BY t.id, t.title, t.created_at, t.updated_at, t.user_id, 
                                             t.full_name, t.email, t.phone_number, t.nationality
                                    ORDER BY t.updated_at DESC
                                ");
                                $stmt->execute();
                                $threads = $stmt->fetchAll();
                                
                                $totalThreads = count($threads);
                                $totalMessages = 0;
                                $totalUsers = [];
                                
                                foreach ($threads as $thread) {
                                    $totalMessages += $thread['message_count'];
                                    
                                    // Count unique users by email or full name
                                    $userKey = $thread['email'] ?: $thread['full_name'] ?: 'anonymous';
                                    $totalUsers[$userKey] = true;
                                    
                                    $title = $thread['title'] ?: 'Untitled Chat';
                                    $messageCount = $thread['message_count'];
                                    $created = date('M j, Y', strtotime($thread['created_at']));
                                    $lastMessage = $thread['last_message_at'] ? date('M j, Y g:i A', strtotime($thread['last_message_at'])) : 'No messages';
                                    
                                    echo '<tr class="clickable-row" onclick="viewThread(\'' . htmlspecialchars($thread['id']) . '\')">';
                                    echo '<td class="thread-id">' . htmlspecialchars(substr($thread['id'], 0, 8)) . '...</td>';
                                    echo '<td>' . htmlspecialchars($title) . '</td>';
                                    
                                    // User Information Column
                                    echo '<td>';
                                    echo '<div class="user-info">';
                                    if ($thread['full_name']) {
                                        echo '<div class="user-name">' . htmlspecialchars($thread['full_name']) . '</div>';
                                    }
                                    if ($thread['email']) {
                                        echo '<div class="user-email">' . htmlspecialchars($thread['email']) . '</div>';
                                    }
                                    if ($thread['phone_number']) {
                                        echo '<div class="user-phone">' . htmlspecialchars($thread['phone_number']) . '</div>';
                                    }
                                    if ($thread['nationality']) {
                                        echo '<div class="user-nationality">' . htmlspecialchars($thread['nationality']) . '</div>';
                                    }
                                    
                                    // Fallback if no user info
                                    if (!$thread['full_name'] && !$thread['email']) {
                                        echo '<span class="badge badge-secondary">Anonymous</span>';
                                    }
                                    echo '</div>';
                                    echo '</td>';
                                    
                                    echo '<td><span class="badge badge-info">' . $messageCount . '</span></td>';
                                    echo '<td>' . $created . '</td>';
                                    echo '<td>' . $lastMessage . '</td>';
                                    echo '<td>';
                                    echo '<button class="btn-secondary" onclick="event.stopPropagation(); viewThread(\'' . htmlspecialchars($thread['id']) . '\')">View</button>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                
                                $uniqueUsers = count($totalUsers);
                            } else {
                                echo '<tr><td colspan="7" class="text-center text-danger">Database connection failed</td></tr>';
                                $totalThreads = $totalMessages = $uniqueUsers = 0;
                            }
                            
                        } catch (Exception $e) {
                            echo '<tr><td colspan="7" class="text-center text-danger">Error loading chat history</td></tr>';
                            error_log("Chat history error: " . $e->getMessage());
                            $totalThreads = $totalMessages = $uniqueUsers = 0;
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div id="empty-state" class="empty-state d-none">
                <h3 class="empty-state-title">No chat history yet</h3>
                <p class="empty-state-text">Start your first conversation to see it here</p>
                <a href="../../app" class="btn-primary">Start Chatting</a>
            </div>
        </div>

        <div id="threadDetail" class="d-none">
            <div class="thread-detail">
                <div class="thread-detail-header">
                    <button class="btn-secondary" onclick="backToList()">
                        Back to Threads
                    </button>
                    <h2 id="threadTitle" class="thread-detail-title">Chat Messages</h2>
                  
                </div>
                
                <div id="messagesContainer" class="messages-container"></div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/jquery-3.7.0.min.js"></script>
    <script src="../../assets/js/jquery.dataTables.min.js"></script>
    <script src="../../assets/js/dataTables.bootstrap5.min.js"></script>
    <script src="/assets/js/common.js"></script>

    <script>
        let table;
        let currentThreadId = null;
        
        $(document).ready(function() {
            const totalThreads = <?php echo $totalThreads ?? 0; ?>;
            const totalMessages = <?php echo $totalMessages ?? 0; ?>;
            const uniqueUsers = <?php echo $uniqueUsers ?? 0; ?>;
            
            updateStats(totalThreads, totalMessages, uniqueUsers);
            
            if (totalThreads > 0) {
                initializeTable();
                hideEmptyState();
            } else {
                showEmptyState();
            }
        });

        function initializeTable() {
            table = $('#threadsTable').DataTable({
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                info: true,
                searching: true,
                ordering: true,
                order: [[5, 'desc']], // Sort by last activity
                columnDefs: [
                    { orderable: false, targets: [6] }
                ],
                language: {
                    emptyTable: "No chat threads found",
                    zeroRecords: "No matching threads found",
                    info: "Showing _TOTAL_ threads",
                    infoEmpty: "No threads",
                    infoFiltered: "(filtered from _MAX_ total)",
                    search: "Search threads:"
                }
            });
        }

        function updateStats(totalThreads, totalMessages, uniqueUsers) {
            $('#stats-info').text(`${totalThreads} threads • ${totalMessages} total messages • ${uniqueUsers} users`);
        }

        function showError(message) {
            const alert = document.getElementById('error-alert');
            const text = document.getElementById('error-text');
            
            text.textContent = message;
            alert.classList.remove('d-none');
            
            setTimeout(() => {
                alert.classList.add('d-none');
            }, 5000);
        }

        function showEmptyState() {
            $('#empty-state').removeClass('d-none');
            $('#threads-table-container').addClass('d-none');
        }

        function hideEmptyState() {
            $('#empty-state').addClass('d-none');
            $('#threads-table-container').removeClass('d-none');
        }

        function viewThread(threadId) {
            currentThreadId = threadId;
            document.getElementById('threadList').classList.add('d-none');
            document.getElementById('threadDetail').classList.remove('d-none');
            loadMessages(threadId);
        }

        function backToList() {
            document.getElementById('threadList').classList.remove('d-none');
            document.getElementById('threadDetail').classList.add('d-none');
            currentThreadId = null;
        }

  
        async function loadMessages(threadId) {
            const container = document.getElementById('messagesContainer');
            const titleEl = document.getElementById('threadTitle');
            
            try {
                container.innerHTML = `
                    <div class="message" style="text-align: center; padding: 40px;">
                        <div class="loading-spinner" style="width: 24px; height: 24px;"></div>
                        <p style="margin-top: 16px; color: var(--geist-muted);">Loading messages...</p>
                    </div>
                `;
                
                const response = await fetch(`../../app/api/get_thread_messages.php?thread_id=${encodeURIComponent(threadId)}`);
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                const messages = data.messages || [];
                const threadTitle = data.title || `Thread: ${threadId.substring(0, 8)}`;
                
                titleEl.textContent = threadTitle;
                
                if (messages.length > 0) {
                    let html = '';
                    messages.forEach(msg => {
                        const role = msg.role === 'user' ? 'User' : 'Assistant';
                        const time = new Date(msg.created_at).toLocaleString();
                        
                        html += `
                            <div class="message">
                                <div class="message-header">
                                    <div class="message-role ${msg.role}">
                                        ${role}
                                    </div>
                                    <div class="message-time">${time}</div>
                                </div>
                                <div class="message-content">${escapeHtml(msg.message)}</div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                    container.scrollTop = container.scrollHeight;
                } else {
                    container.innerHTML = `
                        <div class="message" style="text-align: center; padding: 40px;">
                            <h3 style="margin: 0 0 8px 0; color: var(--geist-foreground);">No messages</h3>
                            <p style="margin: 0; color: var(--geist-muted);">This thread doesn't have any messages yet</p>
                        </div>
                    `;
                }
            } catch (error) {
                container.innerHTML = `
                    <div class="message" style="text-align: center; padding: 40px;">
                        <h3 style="margin: 0 0 8px 0; color: var(--geist-foreground);">Error loading messages</h3>
                        <p style="margin: 0; color: var(--geist-muted);">Please try again later</p>
                    </div>
                `;
                showError('Failed to load messages: ' + error.message);
            }
        }
    </script>
</body>
</html>