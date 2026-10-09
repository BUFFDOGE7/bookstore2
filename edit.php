<?php
require 'db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$query = $pdo->prepare('SELECT * FROM books WHERE id = ?');
$query->execute([$id]);
$book = $query->fetch();
$error = '';
$types = ['new', 'used', 'ebook'];

if ($book && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['title', 'release_date', 'cover_path', 'language', 'summary', 'price', 'stock_saldo', 'pages', 'type'] as $field) {
        $book[$field] = trim($_POST[$field] ?? '');
    }

    if ($book['title'] === '' || $book['language'] === '') {
        $error = 'Title and language are required.';
    } elseif (!is_numeric($book['price']) || $book['price'] < 0) {
        $error = 'Enter a valid price.';
    } elseif (!filter_var($book['pages'], FILTER_VALIDATE_INT) || $book['pages'] < 1) {
        $error = 'Page count must be a positive whole number.';
    } elseif (!in_array($book['type'], $types)) {
        $error = 'Choose a book type.';
    } else {
        $sql = 'UPDATE books SET title=?, release_date=?, cover_path=?, language=?, summary=?,
                price=?, stock_saldo=?, pages=?, type=? WHERE id=?';
        $query = $pdo->prepare($sql);
        $query->execute([
            $book['title'], $book['release_date'], $book['cover_path'], $book['language'],
            $book['summary'], round($book['price'], 2), $book['stock_saldo'],
            $book['pages'], $book['type'], $id
        ]);
        header("Location: book.php?id=$id");
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book</title>
</head>
<body>
<?php if (!$book): ?>
    <h1>Book Not Found</h1>
    <a href="index.php">Back to the book list</a>
<?php else: ?>
    <h1>Edit Book</h1>
    <p style="color: red"><?= e($error) ?></p>
    <form method="post" action="edit.php?id=<?= $id ?>">
        <p>Title: <input name="title" value="<?= e($book['title']) ?>" required></p>
        <p>Publication Year: <input name="release_date" type="number" value="<?= e($book['release_date']) ?>" required></p>
        <p>Cover Image URL: <input name="cover_path" value="<?= e($book['cover_path']) ?>"></p>
        <p>Language: <input name="language" value="<?= e($book['language']) ?>" required></p>
        <p>Summary: <textarea name="summary"><?= e($book['summary']) ?></textarea></p>
        <p>Price: <input name="price" type="number" min="0" step="0.01" value="<?= number_format($book['price'], 2, '.', '') ?>" required></p>
        <p>Stock: <input name="stock_saldo" value="<?= e($book['stock_saldo']) ?>" required></p>
        <p>Pages: <input name="pages" type="number" min="1" value="<?= e($book['pages']) ?>" required></p>
        <p>Type:
            <select name="type">
                <?php foreach ($types as $type): ?>
                    <option value="<?= $type ?>" <?= $book['type'] === $type ? 'selected' : '' ?>><?= $type ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <button type="submit">Save Changes</button>
        <a href="book.php?id=<?= $id ?>">Cancel</a>
    </form>
<?php endif; ?>
</body>
</html>
