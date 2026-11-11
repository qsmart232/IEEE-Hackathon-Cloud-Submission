<?php
$adminPassword = getenv('ADMIN_PASSWORD');
if ($adminPassword === false || $adminPassword === '') {
    http_response_code(503);
    exit('Admin access is not configured. Set ADMIN_PASSWORD in the server environment.');
}

session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

if (!isset($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || !hash_equals($_SESSION['admin_csrf'], $csrfToken)) {
        http_response_code(400);
        exit('Invalid request token. Refresh the page and try again.');
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'login') {
        $submittedPassword = $_POST['password'] ?? '';
        if (is_string($submittedPassword) && hash_equals($adminPassword, $submittedPassword)) {
            session_regenerate_id(true);
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
            header('Location: admin.php');
            exit;
        }
        $loginError = 'Incorrect password. Please try again.';
    } elseif ($action === 'logout' && !empty($_SESSION['admin_authenticated'])) {
        $_SESSION = [];
        session_destroy();
        header('Location: admin.php');
        exit;
    } elseif ($action === 'review' && !empty($_SESSION['admin_authenticated'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $reviewAction = $_POST['review_action'] ?? '';
        $statuses = [
            'approve' => 'Approved',
            'reject' => 'Rejected',
        ];

        if ($id === false || $id === null || $id < 1 || !isset($statuses[$reviewAction])) {
            http_response_code(400);
            exit('Invalid submission action.');
        }

        require_once __DIR__ . DIRECTORY_SEPARATOR . 'dbconnection.php';
        $stmt = $conn->prepare('UPDATE sub SET status = ? WHERE id = ?');
        $stmt->execute([$statuses[$reviewAction], $id]);

        header('Location: admin.php');
        exit;
    } else {
        http_response_code(400);
        exit('Invalid admin action.');
    }
}

if (empty($_SESSION['admin_authenticated'])) {
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin login | Project Submission Portal</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <header class="site-header">
        <a class="brand" href="index.php">Project Submission Portal</a>
        <nav class="site-nav" aria-label="Main navigation">
            <a href="index.php">Home</a>
        </nav>
    </header>
    <main class="login-main">
        <section class="form-card login-card">
            <h1>Admin login</h1>
            <p>Enter the admin password to review project submissions.</p>
            <?php if ($loginError !== ''): ?>
                <div class="notice notice-error" role="alert"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form method="post" class="submission-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="login">
                <label class="field">
                    <span>Password</span>
                    <input type="password" name="password" autocomplete="current-password" required autofocus>
                </label>
                <button class="button button-primary button-wide" type="submit">Sign in</button>
            </form>
        </section>
    </main>
    <footer class="site-footer">Project Submission Portal · <?= date('Y') ?></footer>
    </body>
    </html>
    <?php
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'dbconnection.php';

$stmt = $conn->query('SELECT * FROM sub ORDER BY id DESC');
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$pendingCount = 0;
$approvedCount = 0;
$rejectedCount = 0;

foreach ($submissions as $submission) {
    if ($submission['status'] === 'Pending') {
        $pendingCount++;
    } elseif ($submission['status'] === 'Approved') {
        $approvedCount++;
    } elseif ($submission['status'] === 'Rejected') {
        $rejectedCount++;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#102a43">
    <title>Review submissions | CureConnect</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php" aria-label="CureConnect home">
        <span class="brand-mark" aria-hidden="true">C</span>
        <span>CureConnect<span class="brand-dot">.</span></span>
    </a>
    <nav class="site-nav" aria-label="Main navigation">
        <a href="index.php">Home</a>
        <a href="form.php">Submit a project</a>
        <span class="nav-label">ADMIN</span>
    </nav>
</header>

<main class="admin-shell">
    <section class="admin-heading">
        <div>
            <p class="eyebrow"><span class="eyebrow-line"></span> WORKSPACE / SUBMISSIONS</p>
            <h1>Project review</h1>
            <p class="intro-copy">Review incoming projects and update their status.</p>
        </div>
        <div class="admin-controls">
            <a class="button button-secondary" href="form.php">View submission form</a>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="logout">
                <button class="button button-secondary" type="submit">Log out</button>
            </form>
        </div>
    </section>

    <section class="stats-grid" aria-label="Submission totals">
        <article class="stat-card stat-total">
            <span class="stat-label">Total projects</span>
            <strong><?= count($submissions) ?></strong>
            <span class="stat-caption">All submissions</span>
        </article>
        <article class="stat-card">
            <span class="stat-label"><span class="status-dot dot-pending"></span> Awaiting review</span>
            <strong><?= $pendingCount ?></strong>
            <span class="stat-caption">Need your attention</span>
        </article>
        <article class="stat-card">
            <span class="stat-label"><span class="status-dot dot-approved"></span> Approved</span>
            <strong><?= $approvedCount ?></strong>
            <span class="stat-caption">Ready to move forward</span>
        </article>
        <article class="stat-card">
            <span class="stat-label"><span class="status-dot dot-rejected"></span> Rejected</span>
            <strong><?= $rejectedCount ?></strong>
            <span class="stat-caption">Reviewed submissions</span>
        </article>
    </section>

    <section class="table-card">
        <div class="table-heading">
            <div>
                <h2>All submissions</h2>
                <p>Newest projects appear first.</p>
            </div>
            <span class="result-count"><?= count($submissions) ?> <?= count($submissions) === 1 ? 'project' : 'projects' ?></span>
        </div>
        <?php if ($submissions === []): ?>
            <div class="empty-state">
                <span class="empty-icon" aria-hidden="true">✦</span>
                <h3>No projects yet</h3>
                <p>New submissions will appear here.</p>
            </div>
        <?php else: ?>
            <div class="table-scroll">
                <table class="submissions-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Project file</th>
                            <th>Status</th>
                            <th><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($submissions as $row): ?>
                        <?php
                        $statusClass = strtolower((string) $row['status']);
                        $statusClass = in_array($statusClass, ['pending', 'approved', 'rejected'], true) ? $statusClass : 'unknown';
                        ?>
                        <tr>
                            <td>
                                <div class="student-cell">
                                    <span class="avatar" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr((string) $row['name'], 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                                    <span>
                                        <strong><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <small><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></small>
                                    </span>
                                </div>
                            </td>
                            <td class="muted-cell"><?= htmlspecialchars($row['sid'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="file-chip"><span aria-hidden="true">PDF</span> <?= htmlspecialchars($row['original_filename'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td><span class="status-pill status-<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="actions-cell">
                                <?php if ($row['status'] === 'Pending'): ?>
                                    <form method="post" class="action-form">
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="action" value="review">
                                        <button class="button button-approve" type="submit" name="review_action" value="approve">Approve</button>
                                        <button class="button button-reject" type="submit" name="review_action" value="reject">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span class="reviewed-label">Reviewed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="site-footer">
    <span>© <?= date('Y') ?> CureConnect</span>
    <span>Admin workspace</span>
</footer>
</body>
</html>
