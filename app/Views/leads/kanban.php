<?= $this->extend('layouts/desktop') ?>

<?= $this->section('content') ?>
<?php $tones = ['warn' => 'warn-text', 'info' => 'primary-text', 'ok' => 'ok-text', 'muted' => 'muted']; ?>
<div class="desktop__head">
  <div class="stack" style="gap: 4px">
    <h1>문의</h1>
    <span class="t13 muted">진행 중 <?= $active_n ?>건 · 이번 달 계약 <?= $contracts ?>건</span>
  </div>
  <div class="desktop__tools">
    <form method="get" action="<?= site_url('leads') ?>">
      <label class="search" style="width: 280px; max-width: 100%; background: #fff; border: 1px solid var(--border)">
        <svg class="i" width="18" height="18" viewBox="0 0 24 24" style="color: var(--muted)" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <span class="sr-only">문의 검색</span>
        <input type="search" name="q" placeholder="이름, 전화번호, 주소">
      </label>
    </form>
    <a href="<?= site_url('leads/new') ?>" class="btn btn--primary">+ 문의 등록</a>
  </div>
</div>

<div class="kanban-scroll">
  <div class="kanban">
    <?php foreach ($columns as $col) : ?>
      <section class="kanban__col" aria-label="<?= esc($col['name']) ?>" data-stage="<?= esc($col['stage']) ?>">
        <h2><?= esc($col['name']) ?> <span class="<?= $col['stage'] === 'new' ? 'warn-text' : ($col['stage'] === 'contract' ? 'ok-text' : 'muted') ?>"><?= $col['count'] ?></span></h2>
        <?php foreach ($col['cards'] as $c) : ?>
          <a href="<?= site_url($c['url']) ?>" draggable="true" data-lead="<?= $c['id'] ?>" class="kanban__card<?= $c['alert'] ? ' kanban__card--alert' : '' ?>">
            <span class="t11 <?= $c['tone'] === 'muted' ? 'muted' : $tones[$c['tone']] ?>"><?= esc($c['top']) ?></span>
            <span style="font-size: 14px; font-weight: 700"><?= esc($c['name']) ?> 님</span>
            <span class="t12 muted"><?= esc($c['desc']) ?></span>
          </a>
        <?php endforeach ?>
        <?php if (! $col['cards']) : ?><p class="kanban__empty">비어 있어요</p><?php endif ?>
        <?php if ($col['more']) : ?>
          <a class="kanban__more" href="<?= site_url('leads?stage=' . $col['stage']) ?>">+ <?= $col['more'] ?>건 더 보기</a>
        <?php endif ?>
      </section>
    <?php endforeach ?>
  </div>
</div>
<p class="t12 muted" style="margin: 0">카드를 다른 열로 끌어 놓으면 단계가 바뀌고, 기록에 자동으로 남습니다.</p>

<script>
// 카드를 끌어 다른 열에 놓으면 서버에 단계 변경을 요청한다
(function () {
  var dragged = null;
  document.querySelectorAll('.kanban__card').forEach(function (card) {
    card.addEventListener('dragstart', function () { dragged = card; card.classList.add('kanban__card--dragging'); });
    card.addEventListener('dragend', function () { card.classList.remove('kanban__card--dragging'); dragged = null; });
  });
  document.querySelectorAll('.kanban__col').forEach(function (col) {
    col.addEventListener('dragover', function (e) { e.preventDefault(); col.classList.add('kanban__col--over'); });
    col.addEventListener('dragleave', function () { col.classList.remove('kanban__col--over'); });
    col.addEventListener('drop', function (e) {
      e.preventDefault();
      col.classList.remove('kanban__col--over');
      if (!dragged || dragged.closest('.kanban__col') === col) { return; }
      var body = new URLSearchParams({ stage: col.dataset.stage });
      fetch('<?= site_url('leads') ?>/' + dragged.dataset.lead + '/move', { method: 'POST', body: body })
        .then(function (r) { if (r.ok) { location.reload(); } });
    });
  });
})();
</script>
<?= $this->endSection() ?>
