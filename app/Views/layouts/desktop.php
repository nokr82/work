<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title ?? company('name')) ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;700&display=swap">
<link rel="stylesheet" href="<?= base_url('css/styles.css') ?>">
</head>
<body>
<div class="desktop">
  <?= $this->include('partials/side_nav') ?>
  <main class="desktop__main">
    <?= $this->renderSection('content') ?>
  </main>
</div>
</body>
</html>
