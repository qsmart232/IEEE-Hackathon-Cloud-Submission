<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';
    $statuses = [
        'approve' => 'Approved',
        'reject' => 'Rejected',
    ];

    if ($id === false || $id === null || $id < 1 || !isset($statuses[$action])) {
        http_response_code(400);
        exit('Invalid submission action.');
    }

    $stmt = $conn->prepare('UPDATE sub SET status = ? WHERE id = ?');
    $stmt->execute([$statuses[$action], $id]);

    header('Location: admin.php');
    exit;
}

$stmt = $conn->query('SELECT * FROM sub');
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Submission administration</title>
</head>
<body>
<table border="1">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>SID</th>
            <th>Original file name</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($submissions as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($row['sid'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($row['original_filename'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
                <?php if ($row['status'] === 'Pending'): ?>
                    <form method="post">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" name="action" value="approve">Approve</button>
                        <button type="submit" name="action" value="reject">Reject</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
