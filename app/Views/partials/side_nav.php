<?php
// PC 좌측 메뉴. $active: home | leads | quote | prices | settings
$active = $active ?? '';
$items = [
    'home'     => ['홈',     '/',           0],
    'leads'    => ['문의',   'leads/kanban', 2],
    'quote'    => ['견적',   'quotes/edit', 0],
    'prices'   => ['단가표', 'prices',      0],
    'settings' => ['설정',   '/',           0],
];
?>
<nav class="side-nav" aria-label="주 메뉴">
  <div style="padding: 0 8px 20px">
    <div class="logo"><div class="logo__mark">한</div><span class="bold t15">한빛인테리어</span></div>
  </div>
  <?php foreach ($items as $key => [$label, $path, $count]) : ?>
    <a href="<?= site_url($path) ?>"<?= $active === $key ? ' class="active" aria-current="page"' : '' ?>>
      <?= esc($label) ?>
      <?php if ($count) : ?><span class="side-nav__count"><?= $count ?></span><?php endif ?>
    </a>
  <?php endforeach ?>
</nav>
