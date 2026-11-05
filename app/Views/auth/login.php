<?= $this->extend('layouts/mobile') ?>

<?= $this->section('content') ?>
<div class="pad" style="padding: 48px 20px 0; gap: 20px">
  <div style="display: flex; align-items: center; gap: 10px">
    <div class="logo__mark"><?= esc(mb_substr(company('name'), 0, 1)) ?></div>
    <span class="t15 bold"><?= esc(company('name')) ?> · 사장님</span>
  </div>
  <h1 style="font-size: 22px; font-weight: 700">로그인</h1>

  <?php if (! $configured) : ?>
    <div class="flash flash--error">로그인 비밀번호가 아직 설정되지 않았어요. 서버의 .env 파일에 OWNER_PASSWORD 값을 넣어 주세요.</div>
  <?php else : ?>
    <?php if ($error) : ?><div class="flash flash--error"><?= esc($error) ?></div><?php endif ?>
    <form method="post" action="<?= site_url('login') ?>" class="stack" style="gap: 12px"><?= csrf_field() ?>
      <label class="field"><span>비밀번호</span><input class="input" type="password" name="password" autocomplete="current-password" required autofocus></label>
      <button class="btn btn--primary btn--lg">로그인</button>
    </form>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
