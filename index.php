<?php
require 'db.php';
$books = $pdo->query('SELECT id, title FROM books ORDER BY title')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book List</title>
</head>
<body>
    <h1>Book List</h1>
    <ul>
        <?php foreach ($books as $book): ?>
            <li><a href="book.php?id=<?= $book['id'] ?>"><?= e($book['title']) ?></a></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
