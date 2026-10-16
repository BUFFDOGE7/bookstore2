<?php
require 'db.php';
$search = trim($_GET['search'] ?? '');
$query = $pdo->prepare('SELECT id, title FROM books WHERE is_deleted = 0 AND title LIKE ? ORDER BY title');
$query->execute(['%' . $search . '%']);
$books = $query->fetchAll();
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
    <p><a href="add_author.php">Add Author</a></p>
    <form method="get" action="index.php">
        <label for="search">Search books by title:</label>
        <input type="search" id="search" name="search" value="<?= e($search) ?>">
        <button type="submit">Search</button>
        <?php if ($search !== ''): ?><a href="index.php">Clear</a><?php endif; ?>
    </form>
    <ul>
        <?php foreach ($books as $book): ?>
            <li><a href="book.php?id=<?= $book['id'] ?>"><?= e($book['title']) ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php if (!$books): ?><p><?= $search === '' ? 'There are no books to show.' : 'No books matched your search.' ?></p><?php endif; ?>
</body>
</html>
