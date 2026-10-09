<?php
require 'db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$book = false;
$authors = [];

if ($id > 0) {
    $query = $pdo->prepare('SELECT * FROM books WHERE id = ?');
    $query->execute([$id]);
    $book = $query->fetch();

    if ($book) {
        $query = $pdo->prepare(
            'SELECT a.first_name, a.last_name FROM authors a
             JOIN book_authors ba ON ba.author_id = a.id WHERE ba.book_id = ?'
        );
        $query->execute([$id]);
        $authors = $query->fetchAll();
    }
}
$authorNames = [];
foreach ($authors as $author) {
    $authorNames[] = $author['first_name'] . ' ' . $author['last_name'];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $book ? e($book['title']) : 'Book Not Found' ?></title>
</head>
<body>
<?php if (!$book): ?>
    <h1>Book Not Found</h1>
<?php else: ?>
    <h1><?= e($book['title']) ?></h1>
    <?php if ($book['cover_path']): ?>
        <p><img src="<?= e($book['cover_path']) ?>" alt="Book cover" style="max-width: 250px"></p>
    <?php endif; ?>
    <p><b>Author(s):</b> <?= e(implode(', ', $authorNames) ?: 'No author listed') ?></p>
    <p><b>Publication Year:</b> <?= e($book['release_date']) ?></p>
    <p><b>Language:</b> <?= e($book['language']) ?></p>
    <p><b>Type:</b> <?= e($book['type']) ?></p>
    <p><b>Price:</b> <?= number_format($book['price'], 2, '.', ',') ?> €</p>
    <p><b>Stock:</b> <?= e($book['stock_saldo']) ?></p>
    <p><b>Pages:</b> <?= e($book['pages']) ?></p>
    <p><b>Summary:</b> <?= nl2br(e($book['summary'] ?: 'No description available')) ?></p>
    <p><a href="edit.php?id=<?= $book['id'] ?>">Edit Book</a></p>
    <p><a href="delete.php?id=<?= $book['id'] ?>">Delete Book</a></p>
<?php endif; ?>
    <p><a href="index.php">Back to the book list</a></p>
</body>
</html>
