<?php
/**
 * Main App Layout
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?><?= APP_TITLE ?> Monitor</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/all.min.css" rel="stylesheet">
    <?php if (isset($extraCss)): ?>
        <?= $extraCss ?>
    <?php endif; ?>
    <style>
        :root { --primary: #059669; --bg: #f0fdf4; --card: #ffffff; --text: #334155; }
        body { background-color: var(--bg); color: var(--text); font-family: 'Segoe UI', sans-serif; }
    </style>
</head>
<body>
    <?= $content ?? '' ?>

    <?php if (isset($extraJs)): ?>
        <?= $extraJs ?>
    <?php endif; ?>
</body>
</html>
