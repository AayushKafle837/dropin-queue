<?php use App\Core\View; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if (!empty($refresh)): ?>
        <meta http-equiv="refresh" content="<?= (int) $refresh ?>">
    <?php endif; ?>
    <title><?= View::e($title ?? 'Drop-in Queue') ?> · Drop-in Queue</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/">Drop-in <span>Queue</span></a>
    <nav>
        <?php if (!empty($_SESSION['facilitator'])): ?>
            <a href="/facilitator">Board</a>
            <form method="post" action="/facilitator/logout" class="inline">
                <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                <button type="submit" class="link">Sign out</button>
            </form>
        <?php else: ?>
            <a href="/facilitator/login">Facilitators</a>
        <?php endif; ?>
    </nav>
</header>

<main class="container">
    <?php if (!empty($flash)): ?>
        <div class="flash flash-<?= View::e($flash['type']) ?>" role="status"><?= View::e($flash['message']) ?></div>
    <?php endif; ?>

    <?= $content ?>
</main>
</body>
</html>
