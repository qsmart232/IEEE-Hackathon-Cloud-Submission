<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'dbconnection.php';
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'upload';
$message = '';
$uploadSucceeded = false;

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
                $message = 'Your project has been submitted successfully.';
                $uploadSucceeded = true;
            } else {
                $message = 'The file could not be uploaded. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#102a43">
    <title>Submit a project | CureConnect</title>
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
        <a class="nav-cta" href="form.php">Submit a project</a>
    </nav>
</header>

<main class="page-shell">
    <section class="intro">
        <p class="eyebrow"><span class="eyebrow-line"></span> HACKATHON PROJECTS</p>
        <h1>Big ideas start<br>with <span class="text-accent">a submission.</span></h1>
        <p class="intro-copy">Share your work with the CureConnect team. We’re excited to see what you’ve built.</p>
        <div class="trust-note"><span class="trust-icon" aria-hidden="true">✓</span> Your project is reviewed by our team</div>
    </section>

    <section class="form-card" aria-labelledby="form-title">
        <div class="card-heading">
            <div>
                <p class="eyebrow">PROJECT DETAILS</p>
                <h2 id="form-title">Submit your project</h2>
            </div>
            <span class="step-badge">01 <span>/ 01</span></span>
        </div>

        <?php if ($message !== ''): ?>
            <div class="notice <?= $uploadSucceeded ? 'notice-success' : 'notice-error' ?>" role="status">
                <span class="notice-icon" aria-hidden="true"><?= $uploadSucceeded ? '✓' : '!' ?></span>
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="submission-form">
            <label class="field">
                <span>Full name</span>
                <input type="text" name="name" placeholder="e.g. Alex Morgan" autocomplete="name" required>
            </label>
            <label class="field">
                <span>Email address</span>
                <input type="email" name="email" placeholder="you@example.com" autocomplete="email" required>
            </label>
            <label class="field">
                <span>Student ID</span>
                <input type="text" name="sid" placeholder="Enter your student ID" required>
            </label>
            <label class="field">
                <span>Project document</span>
                <span class="upload-field">
                    <input type="file" name="project_file" accept=".pdf,application/pdf" required>
                    <span class="upload-caption"><strong>Choose a PDF</strong> or drag it here</span>
                    <span class="upload-hint">PDF format only</span>
                </span>
            </label>
            <button class="button button-primary button-wide" type="submit">
                Send for review <span aria-hidden="true">→</span>
            </button>
            <p class="form-footnote">By submitting, you confirm that the information above is correct.</p>
        </form>
    </section>
</main>

<footer class="site-footer">
    <span>© <?= date('Y') ?> CureConnect</span>
    <span>Ideas for a healthier future.</span>
</footer>
</body>
</html>
