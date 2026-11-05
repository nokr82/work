<?php
// PC 좌측 메뉴. $active: home | leads | quote | prices | settings
$active = $active ?? '';
$items = [
    'home'     => ['홈',     '/',           0],
    // 문의 옆 숫자: 아직 응대하지 않은 신규 문의 수
    'leads'    => ['문의',   'leads/kanban', (new \App\Models\LeadModel())->where('stage', 'new')->countAllResults()],
    'quote'    => ['견적',   'quotes/edit', 0],
    'prices'   => ['단가표', 'prices',      0],
    'settings' => ['설정',   'settings',    0],
];
?>
<nav class="side-nav" aria-label="주 메뉴">
  <div style="padding: 0 8px 20px">
    <div class="logo"><div class="logo__mark"><?= esc(mb_substr(company('name'), 0, 1)) ?></div><span class="bold t15"><?= esc(company('name')) ?></span></div>
  </div>
  <?php foreach ($items as $key => [$label, $path, $count]) : ?>
    <a href="<?= site_url($path) ?>"<?= $active === $key ? ' class="active" aria-current="page"' : '' ?>>
      <?= esc($label) ?>
      <?php if ($count) : ?><span class="side-nav__count"><?= $count ?></span><?php endif ?>
    </a>
  <?php endforeach ?>
  <form method="post" action="<?= site_url('logout') ?>" style="margin-top: auto; padding: 0 8px"><?= csrf_field() ?>
    <button class="btn btn--sm" style="width: 100%">로그아웃</button>
  </form>
</nav>
