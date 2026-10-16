<?php
require 'db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$message = '';
$deleted = false;

if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = $pdo->prepare('UPDATE books SET is_deleted = 1 WHERE id = ? AND is_deleted = 0');
    $query->execute([$id]);
    if ($query->rowCount() > 0) {
        $deleted = true;
    } else {
        $message = 'This book does not exist or has already been deleted.';
    }
} elseif ($id <= 0) {
    $message = 'A valid book ID is required.';
} elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $message = 'Submit the delete form to delete a book.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Book</title>
</head>
<body>
    <h1><?= $deleted ? 'Book Deleted' : 'Unable to Delete Book' ?></h1>
    <p><?= $deleted ? 'The book was hidden from the book list.' : e($message) ?></p>
    <p><a href="index.php">Back to the book list</a></p>
</body>
</html>
