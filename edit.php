<?php
require 'db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$query = $pdo->prepare('SELECT * FROM books WHERE id = ? AND is_deleted = 0');
$query->execute([$id]);
$book = $query->fetch();
$error = '';
$types = ['new', 'used', 'ebook'];

if ($book && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_book';

    if ($action === 'add_author') {
        $authorId = filter_var($_POST['author_id'] ?? '', FILTER_VALIDATE_INT);
        if ($authorId) {
            $query = $pdo->prepare(
                'SELECT authors.id, book_authors.author_id
                 FROM authors LEFT JOIN book_authors
                 ON authors.id = book_authors.author_id AND book_authors.book_id = ?
                 WHERE authors.id = ?'
            );
            $query->execute([$id, $authorId]);
            $author = $query->fetch();

            if (!$author) {
                $error = 'Choose an author from the list.';
            } elseif ($author['author_id'] !== null) {
                $error = 'This author is already assigned to the book.';
            } else {
                $query = $pdo->prepare('INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)');
                $query->execute([$id, $authorId]);
                header("Location: edit.php?id=$id&author_added=1");
                exit;
            }
        } else {
            $error = 'Choose an author from the list.';
        }
    } elseif ($action === 'remove_author') {
        $authorId = filter_var($_POST['author_id'] ?? '', FILTER_VALIDATE_INT);
        if ($authorId) {
            $query = $pdo->prepare('DELETE FROM book_authors WHERE book_id = ? AND author_id = ?');
            $query->execute([$id, $authorId]);
            if ($query->rowCount() > 0) {
                header("Location: edit.php?id=$id&author_removed=1");
                exit;
            }
            $error = 'This author is not assigned to the book.';
        } else {
            $error = 'The author could not be identified.';
        }
    } else {
        foreach (['title', 'release_date', 'cover_path', 'language', 'summary', 'price', 'stock_saldo', 'pages', 'type'] as $field) {
            $book[$field] = trim($_POST[$field] ?? '');
        }

        if ($book['title'] === '' || $book['language'] === '' || $book['stock_saldo'] === '') {
            $error = 'Title, language, and stock are required.';
        } elseif (!preg_match('/^\d{4}$/', $book['release_date']) || $book['release_date'] < 1901 || $book['release_date'] > 2155) {
            $error = 'Enter a valid four-digit year (1901–2155).';
        } elseif (!is_numeric($book['price']) || $book['price'] < 0) {
            $error = 'Enter a valid price.';
        } elseif (!filter_var($book['pages'], FILTER_VALIDATE_INT) || $book['pages'] < 1) {
            $error = 'Page count must be a positive whole number.';
        } elseif (!in_array($book['type'], $types, true)) {
            $error = 'Choose a book type.';
        } else {
            $sql = 'UPDATE books SET title=?, release_date=?, cover_path=?, language=?, summary=?,
                    price=?, stock_saldo=?, pages=?, type=? WHERE id=? AND is_deleted=0';
            $query = $pdo->prepare($sql);
            $query->execute([
                $book['title'], $book['release_date'], $book['cover_path'], $book['language'],
                $book['summary'], number_format((float) $book['price'], 2, '.', ''), $book['stock_saldo'],
                $book['pages'], $book['type'], $id
            ]);
            header("Location: edit.php?id=$id&updated=1");
            exit;
        }
    }
}

$bookAuthors = [];
$availableAuthors = [];
if ($book) {
    $query = $pdo->prepare(
        'SELECT a.id, a.first_name, a.last_name FROM authors a
         JOIN book_authors ba ON ba.author_id = a.id WHERE ba.book_id = ?
         ORDER BY a.last_name, a.first_name'
    );
    $query->execute([$id]);
    $bookAuthors = $query->fetchAll();

    $query = $pdo->prepare(
        'SELECT id, first_name, last_name FROM authors
         WHERE id NOT IN (SELECT author_id FROM book_authors WHERE book_id = ?)
         ORDER BY last_name, first_name'
    );
    $query->execute([$id]);
    $availableAuthors = $query->fetchAll();
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
    <?php if ($error): ?><p style="color: red"><?= e($error) ?></p><?php endif; ?>
    <?php if (isset($_GET['updated'])): ?><p>Book details saved.</p><?php endif; ?>
    <?php if (isset($_GET['author_added'])): ?><p>Author added to the book.</p><?php endif; ?>
    <?php if (isset($_GET['author_removed'])): ?><p>Author removed from the book.</p><?php endif; ?>
    <form method="post" action="edit.php?id=<?= $id ?>">
        <input type="hidden" name="action" value="save_book">
        <p>Title: <input name="title" value="<?= e($book['title']) ?>" required></p>
        <p>Publication Year: <input name="release_date" type="number" min="1901" max="2155" step="1" value="<?= e($book['release_date']) ?>" required></p>
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

    <h2>Authors</h2>
    <?php if ($bookAuthors): ?>
        <?php foreach ($bookAuthors as $author): ?>
            <div>
                <?= e($author['first_name'] . ' ' . $author['last_name']) ?>
                <form method="post" action="edit.php?id=<?= $id ?>">
                    <input type="hidden" name="action" value="remove_author">
                    <input type="hidden" name="author_id" value="<?= $author['id'] ?>">
                    <button type="submit">Remove Author</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No authors are assigned to this book.</p>
    <?php endif; ?>

    <?php if ($availableAuthors): ?>
        <form method="post" action="edit.php?id=<?= $id ?>">
            <input type="hidden" name="action" value="add_author">
            <label for="author_id">Add an author:</label>
            <select name="author_id" id="author_id" required>
                <?php foreach ($availableAuthors as $author): ?>
                    <option value="<?= $author['id'] ?>"><?= e($author['first_name'] . ' ' . $author['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Add Author</button>
        </form>
    <?php else: ?>
        <p>There are no more authors available to add.</p>
    <?php endif; ?>
<?php endif; ?>
</body>
</html>
