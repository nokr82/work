<?= $this->extend('layouts/mobile') ?>

<?= $this->section('content') ?>
<?php
$phoneIcon = '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>';
?>
<div class="pad" style="padding: 24px 20px 0; gap: 20px">
  <div class="row">
    <div class="stack" style="gap: 4px">
      <span class="t13 muted">한빛인테리어</span>
      <h1 style="font-size: 24px; font-weight: 700">오늘 할 일 <span style="color: var(--primary)"><?= count($todos) ?>건</span></h1>
    </div>
    <button class="icon-btn" aria-label="알림">
      <svg class="i" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
    </button>
  </div>

  <div class="stat-grid">
    <?php foreach ($stats as $s) : ?>
      <div class="stat"><span class="t11 muted"><?= esc($s['label']) ?></span><b><?= esc($s['value']) ?></b></div>
    <?php endforeach ?>
  </div>

  <div class="stack" style="gap: 10px">
    <h2 class="section-title">지금 연락하면 좋은 고객</h2>

    <?php if (! $todos) : ?>
      <div class="card t13 muted" style="text-align: center">오늘 급하게 연락할 고객이 없어요.</div>
    <?php endif ?>

    <?php foreach ($todos as $t) : $l = $t['lead']; ?>
      <?php if ($t['type'] === 'quote_stale') : ?>
        <div class="card card--alert stack" style="padding: 16px; gap: 10px">
          <div><span class="badge badge--warn"><?= esc($t['label']) ?></span></div>
          <div class="stack" style="gap: 2px">
            <span style="font-size: 16px; font-weight: 700"><?= esc($l['name']) ?> 님</span>
            <span class="t13 muted"><?= esc($t['desc']) ?></span>
          </div>
          <div style="display: flex; gap: 8px">
            <a href="tel:<?= esc($l['phone']) ?>" class="btn btn--primary btn--grow">
              <svg class="i" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><?= $phoneIcon ?></svg>
              전화하기
            </a>
            <a href="<?= site_url('leads/' . $l['id']) ?>" class="btn">상세</a>
          </div>
        </div>
      <?php else : ?>
        <div class="card row">
          <div class="stack" style="gap: 4px; min-width: 0">
            <span class="t12 <?= in_array($t['type'], ['new_unanswered', 'no_quote'], true) ? 'warn-text' : 'primary-text' ?>"><?= esc($t['label']) ?></span>
            <span class="t15 bold"><?= esc($l['name']) ?> 님</span>
            <span class="t13 muted"><?= esc($t['desc']) ?></span>
          </div>
          <?php if ($t['type'] === 'new_unanswered') : ?>
            <a href="tel:<?= esc($l['phone']) ?>" class="icon-btn icon-btn--soft" aria-label="<?= esc($l['name']) ?> 님에게 전화" style="color: var(--primary)">
              <svg class="i" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><?= $phoneIcon ?></svg>
            </a>
          <?php elseif ($t['type'] === 'no_quote') : ?>
            <a href="<?= site_url('quotes/new/' . $l['id']) ?>" class="btn btn--soft" style="flex-shrink: 0; padding: 0 14px; font-size: 13px">견적 쓰기</a>
          <?php else : ?>
            <a href="<?= site_url('leads/' . $l['id']) ?>" class="icon-btn" aria-label="<?= esc($l['name']) ?> 님 문의 상세" style="color: var(--text)">
              <svg class="i" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </a>
          <?php endif ?>
        </div>
      <?php endif ?>
    <?php endforeach ?>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('floating') ?>
<a href="<?= site_url('leads/new') ?>" class="fab fab--round" aria-label="문의 등록">
  <svg class="i" width="24" height="24" viewBox="0 0 24 24" stroke-width="2.2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
</a>
<?= $this->endSection() ?>
