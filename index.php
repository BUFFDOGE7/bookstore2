<?php
require 'db.php';
$search = trim($_GET['search'] ?? '');
$query = $pdo->prepare('SELECT id, title FROM books WHERE is_deleted = 0 AND title LIKE ? ORDER BY title');
$query->execute(['%' . $search . '%']);
$books = $query->fetchAll();
?>
<!doctype html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book List</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @custom-variant dark (&:where(.dark, .dark *));
    </style>
</head>
<body class="scheme-dark min-h-screen bg-slate-950 text-slate-100 antialiased">
    <main class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <header class="mb-8 flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Bookstore</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight">Book List</h1>
            </div>
            <a class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-400" href="add%20author.php">Add Author</a>
        </header>

        <form class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900" method="get" action="index.php">
            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300" for="search">Search books by title</label>
            <div class="flex flex-col gap-3 sm:flex-row">
                <input class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" type="search" id="search" name="search" value="<?= e($search) ?>" placeholder="Enter a book title">
                <button class="rounded-lg bg-indigo-600 px-5 py-2 font-semibold text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-400" type="submit">Search</button>
                <?php if ($search !== ''): ?><a class="rounded-lg px-4 py-2 text-center font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" href="index.php">Clear</a><?php endif; ?>
            </div>
        </form>

        <ul class="space-y-3">
            <?php foreach ($books as $book): ?>
                <li class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm transition hover:border-indigo-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-700">
                    <a class="font-medium text-slate-800 hover:text-indigo-600 dark:text-slate-100 dark:hover:text-indigo-400" href="book.php?id=<?= $book['id'] ?>"><?= e($book['title']) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if (!$books): ?>
            <p class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-slate-500 dark:border-slate-700 dark:text-slate-400">
                <?= $search === '' ? 'There are no books to show.' : 'No books matched your search.' ?>
            </p>
        <?php endif; ?>
    </main>
</body>
</html>
