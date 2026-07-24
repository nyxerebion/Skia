<?php
// admin/logs.php
require_once '../core/bootstrap.php';

if (!isAdmin()) {
    setFlashMessage('Unauthorized access', 'error');
    http_response_code(403);
    header('Location: ../index.php');
    exit;
}

// Get filters
$ipFilter = $_GET['ip'] ?? '';
$actionFilter = $_GET['action'] ?? '';
$limit = (int)($_GET['limit'] ?? 50);

// Get rate limit history
$history = getRateLimitHistory($pdo, $ipFilter, $actionFilter, $limit);

// Get all actions for filter dropdown
$actions = $pdo->query("SELECT DISTINCT action FROM rate_limits ORDER BY action")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>

    <title>Rate Limit History</title>
    <style>
        body {
            font-family: Arial;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
        }

        .filter-form {
            margin-bottom: 20px;
        }

        .filter-form input,
        .filter-form select {
            padding: 8px;
            margin-right: 10px;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>
    <h1>Rate Limit History</h1>

    <form method="GET" class="filter-form">
        <input type="text" name="ip" placeholder="Filter by IP" value="<?php echo htmlspecialchars($ipFilter); ?>">
        <select name="action">
            <option value="">All Actions</option>
            <?php foreach ($actions as $a): ?>
                <option value="<?php echo htmlspecialchars($a['action']); ?>" <?php echo $actionFilter === $a['action'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($a['action']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="limit" placeholder="Limit" value="<?php echo $limit; ?>" min="1" max="500">
        <button type="submit">Filter</button>
        <a href="?">Clear</a>
    </form>

    <?php if (count($history) > 0): ?>
        <p><strong>Total records:</strong> <?php echo count($history); ?></p>
        <table>
            <div class="wrapper">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>IP Address</th>
                        <th>Action</th>
                        <th>Attempts</th>
                        <th>First Attempt</th>
                        <th>Last Attempt</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $record): ?>
                        <tr>
                            <td><?php echo $record['id']; ?></td>
                            <td><?= htmlspecialchars($_SESSION['username'] ?? '') ?></td>
                            <td><code><?php echo htmlspecialchars($record['ip_address']); ?></code></td>
                            <td><?php echo htmlspecialchars($record['action']); ?></td>
                            <td><?php echo $record['attempts']; ?></td>
                            <td><?php echo $record['first_attempt']; ?></td>
                            <td><?php echo $record['last_attempt']; ?></td>
                            <td>
                                <span class="badge badge-success">✅ Archived</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </div>
        </table>
    <?php else: ?>
        <p>No archived rate limit records found.</p>
    <?php endif; ?>

    <p style="margin-top: 20px;">
        <a href="../index.php">← Back to Home</a>
    </p>
</body>

</html>