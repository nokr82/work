<?= $this->extend('layouts/mobile') ?>

<?= $this->section('content') ?>
<?php
$labels  = stage_labels();
$chipCls = static function (string $key) use ($stage): string {
    if ((string) $stage === $key) {
        return 'chip chip--active';
    }

    return $key === 'new' ? 'chip chip--warn' : 'chip';
};
?>
<div class="stack" style="background: #fff; padding: 24px 20px 12px; gap: 14px; border-bottom: 1px solid var(--border)">
  <div class="row">
    <h1 style="font-size: 24px; font-weight: 700">문의</h1>
    <a href="<?= site_url('leads/kanban') ?>" class="btn btn--sm">
      <svg class="i" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="18" rx="1.5"/><rect x="14" y="3" width="7" height="11" rx="1.5"/></svg>
      칸반 보기
    </a>
  </div>
  <form method="get" action="<?= site_url('leads') ?>">
    <?php if ($stage) : ?><input type="hidden" name="stage" value="<?= esc($stage) ?>"><?php endif ?>
    <label class="search">
      <svg class="i" width="18" height="18" viewBox="0 0 24 24" style="color: var(--muted)" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <span class="sr-only">문의 검색</span>
      <input type="search" name="q" value="<?= esc($q) ?>" placeholder="이름, 전화번호, 주소 검색">
    </label>
  </form>
  <div class="chip-row">
    <a href="<?= site_url('leads') . ($q !== '' ? '?q=' . rawurlencode($q) : '') ?>" class="<?= $chipCls('') ?>">전체 <?= $total ?></a>
    <?php foreach ($labels as $key => $label) : ?>
      <a href="<?= site_url('leads?stage=' . $key) . ($q !== '' ? '&q=' . rawurlencode($q) : '') ?>" class="<?= $chipCls($key) ?>"><?= esc($label) ?> <?= $counts[$key] ?></a>
    <?php endforeach ?>
  </div>
</div>

<div class="pad" style="gap: 8px">
  <?php foreach ($leads as $l) : ?>
    <a href="<?= site_url('leads/' . $l['id']) ?>" class="card">
      <div class="row">
        <span class="badge badge--<?= stage_tone($l['stage']) ?>" style="padding: 2px 8px"><?= esc(stage_label($l['stage'])) ?></span>
        <span class="t12 muted"><?= esc(time_ago($l['created_at'])) ?> · <?= esc($l['source']) ?></span>
      </div>
      <span class="t15 bold"><?= esc($l['name']) ?> 님</span>
      <span class="t13 muted"><?= esc(\App\Models\LeadModel::summary($l)) ?></span>
    </a>
  <?php endforeach ?>
  <?php if (! $leads) : ?>
    <p class="t13 muted" style="text-align: center">조건에 맞는 문의가 없어요.</p>
  <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('floating') ?>
<a href="<?= site_url('leads/new') ?>" class="fab fab--pill" aria-label="문의 직접 등록">
  <svg class="i" width="20" height="20" viewBox="0 0 24 24" stroke-width="2.2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
  전화 문의 등록
</a>
<?= $this->endSection() ?>
