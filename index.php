<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Project Submission Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php">Project Submission Portal</a>
    <nav class="site-nav" aria-label="Main navigation">
        <a href="index.php">Home</a>
        <a href="form.php">Submit a project</a>
        <a href="admin.php">Admin</a>
    </nav>
</header>

<main class="home-main">
    <section class="home-card">
        <p class="eyebrow">STUDENT PROJECTS</p>
        <h1>Submit your project</h1>
        <p>Use this portal to send your project to the review team. Upload your document as a PDF and check back with your administrator for its status.</p>
        <a class="button button-primary" href="form.php">Open submission form</a>
    </section>

    <section class="how-section">
        <h2>How it works</h2>
        <ol>
            <li>Enter your name, email, and student ID.</li>
            <li>Upload your project as a PDF.</li>
            <li>The admin team will review your submission.</li>
        </ol>
    </section>
</main>

<footer class="site-footer">Project Submission Portal · <?= date('Y') ?></footer>
</body>
</html>
