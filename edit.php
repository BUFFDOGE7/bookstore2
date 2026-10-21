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
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @custom-variant dark (&:where(.dark, .dark *));
    </style>
</head>
<body class="scheme-dark min-h-screen bg-slate-950 text-slate-100 antialiased">
<main class="mx-auto max-w-3xl space-y-6 px-4 py-10 sm:px-6">
<?php if (!$book): ?>
    <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h1 class="text-2xl font-bold">Book Not Found</h1>
        <a class="mt-4 inline-block font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400" href="index.php">Back to the book list</a>
    </section>
<?php else: ?>
    <header>
        <a class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400" href="index.php">← Back to the book list</a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight">Edit Book</h1>
    </header>

    <?php if ($error): ?><p class="rounded-lg border border-rose-300 bg-rose-50 px-4 py-3 text-rose-800 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if (isset($_GET['updated'])): ?><p class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200" role="status">Book details saved.</p><?php endif; ?>
    <?php if (isset($_GET['author_added'])): ?><p class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200" role="status">Author added to the book.</p><?php endif; ?>
    <?php if (isset($_GET['author_removed'])): ?><p class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200" role="status">Author removed from the book.</p><?php endif; ?>

    <form class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900" method="post" action="edit.php?id=<?= $id ?>">
        <input type="hidden" name="action" value="save_book">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Title
            <input class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" name="title" value="<?= e($book['title']) ?>" required>
        </label>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Publication Year
            <input class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" name="release_date" type="number" min="1901" max="2155" step="1" value="<?= e($book['release_date']) ?>" required>
        </label>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Cover Image URL
            <input class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" name="cover_path" value="<?= e($book['cover_path']) ?>">
        </label>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Language
            <input class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" name="language" value="<?= e($book['language']) ?>" required>
        </label>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Summary
            <textarea class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" name="summary" rows="4"><?= e($book['summary']) ?></textarea>
        </label>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Price (€)
            <input class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" name="price" type="number" min="0" step="0.01" value="<?= number_format($book['price'], 2, '.', '') ?>" required>
        </label>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Stock
            <input class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" name="stock_saldo" value="<?= e($book['stock_saldo']) ?>" required>
        </label>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Pages
            <input class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" name="pages" type="number" min="1" value="<?= e($book['pages']) ?>" required>
        </label>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Type
            <select class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" name="type">
                <?php foreach ($types as $type): ?>
                    <option value="<?= $type ?>" <?= $book['type'] === $type ? 'selected' : '' ?>><?= $type ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="flex flex-wrap items-center gap-3 pt-2">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 font-semibold text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-400" type="submit">Save Changes</button>
            <a class="rounded-lg px-4 py-2 font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" href="book.php?id=<?= $id ?>">Cancel</a>
        </div>
    </form>

    <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-xl font-semibold">Authors</h2>
        <?php if ($bookAuthors): ?>
            <ul class="divide-y divide-slate-200 dark:divide-slate-800">
                <?php foreach ($bookAuthors as $author): ?>
                    <li class="flex items-center justify-between gap-4 py-3">
                        <span><?= e($author['first_name'] . ' ' . $author['last_name']) ?></span>
                        <form method="post" action="edit.php?id=<?= $id ?>">
                            <input type="hidden" name="action" value="remove_author">
                            <input type="hidden" name="author_id" value="<?= $author['id'] ?>">
                            <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800" type="submit">Remove</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-sm text-slate-500 dark:text-slate-400">No authors are assigned to this book.</p>
        <?php endif; ?>

        <?php if ($availableAuthors): ?>
            <form class="flex flex-col gap-3 border-t border-slate-200 pt-4 dark:border-slate-800 sm:flex-row" method="post" action="edit.php?id=<?= $id ?>">
                <input type="hidden" name="action" value="add_author">
                <label class="sr-only" for="author_id">Add an author</label>
                <select class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" name="author_id" id="author_id" required>
                    <?php foreach ($availableAuthors as $author): ?>
                        <option value="<?= $author['id'] ?>"><?= e($author['first_name'] . ' ' . $author['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="rounded-lg bg-indigo-600 px-5 py-2 font-semibold text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-400" type="submit">Add Author</button>
            </form>
        <?php else: ?>
            <p class="border-t border-slate-200 pt-4 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">There are no more authors available to add.</p>
        <?php endif; ?>
    </section>
<?php endif; ?>
</main>
</body>
</html>
