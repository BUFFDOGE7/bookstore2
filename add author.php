<?php
require 'db.php';

$firstName = '';
$lastName = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');

    if ($firstName === '' || $lastName === '') {
        $error = 'Enter both the first and last name.';
    } else {
        $query = $pdo->prepare('INSERT INTO authors (first_name, last_name) VALUES (?, ?)');
        $query->execute([$firstName, $lastName]);
        header('Location: add%20author.php?added=1');
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Author</title>
</head>
<body>
    <h1>Add Author</h1>
    <?php if (isset($_GET['added'])): ?>
        <p role="status">Author added successfully.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p role="alert"><?= e($error) ?></p>
    <?php endif; ?>
    <form method="post" action="add%20author.php">
        <p>First Name: <input name="first_name" maxlength="45" value="<?= e($firstName) ?>" required></p>
        <p>Last Name: <input name="last_name" maxlength="45" value="<?= e($lastName) ?>" required></p>
        <button type="submit">Add Author</button>
    </form>
    <p><a href="index.php">Back to the book list</a></p>
</body>
</html>
