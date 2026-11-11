<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'dbconnection.php';
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'upload';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['project_file']) || $_FILES['project_file']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Please choose a file to upload.';
    } else {
        $file = $_FILES['project_file'];
        $originalFileName = $file['name'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($file['tmp_name']);

        if ($extension !== 'pdf' || $mimeType !== 'application/pdf') {
            $message = 'Only PDF files are allowed.';
        } elseif (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            $message = 'The upload folder could not be created.';
        } else {
            $fileName = bin2hex(random_bytes(16)) . '.pdf';
            $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $sid = trim($_POST['sid']);

    $stmt = $conn->prepare("
        INSERT INTO sub
        (name, email, sid, filename, original_filename, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $name,
        $email,
        $sid,
        $fileName,
        $originalFileName,
        'Pending'
    ]);
                $message = 'PDF uploaded successfully.';
            } else {
                $message = 'The file could not be uploaded.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Project submission</title>
</head>
<body>
<?php if ($message !== ''): ?>
    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label>Full name: <input type="text" name="name" required></label><br><br>
    <label>Email: <input type="email" name="email" required></label><br><br>
    <label>Student ID: <input type="text" name="sid" required></label><br><br>
    <label>Upload project file (PDF only): <input type="file" name="project_file" accept=".pdf,application/pdf" required></label><br><br>
    <button type="submit">Submit</button>
</form>
</body>
</html>
