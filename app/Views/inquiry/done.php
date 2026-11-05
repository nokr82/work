<?= $this->extend('layouts/mobile') ?>

<?= $this->section('content') ?>
<div class="stack" style="flex-grow: 1; justify-content: center; align-items: center; gap: 12px; padding: 40px 20px; text-align: center">
  <div class="logo__mark" style="width: 56px; height: 56px; border-radius: 28px; font-size: 24px; background: var(--ok)">✓</div>
  <h1 style="font-size: 22px; font-weight: 700">문의가 접수되었어요</h1>
  <p class="t13 muted" style="margin: 0; line-height: 1.6"><?= esc(company('name')) ?>에서<br>보통 2시간 안에 연락드릴게요.</p>
</div>
<?= $this->endSection() ?>
