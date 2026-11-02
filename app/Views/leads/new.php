<?= $this->extend('layouts/mobile') ?>

<?= $this->section('content') ?>
<div class="top-header" style="padding: 12px 12px 12px">
  <div style="display: flex; align-items: center; gap: 4px">
    <a href="<?= site_url('leads') ?>" class="back-link" aria-label="문의 목록으로" style="color: var(--text)">
      <svg class="i" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
    </a>
    <h1 style="font-size: 18px; font-weight: 700">전화 문의 등록</h1>
  </div>
</div>

<form method="post" action="<?= site_url('leads/create') ?>" class="pad" style="gap: 16px">
  <?php foreach ($errors as $e) : ?><div class="flash flash--error"><?= esc($e) ?></div><?php endforeach ?>

  <label class="field"><span>이름</span><input class="input" type="text" name="name" value="<?= esc(old('name')) ?>" required></label>
  <label class="field"><span>연락처</span><input class="input" type="tel" name="phone" value="<?= esc(old('phone')) ?>" placeholder="010-0000-0000" required></label>
  <label class="field"><span>문의 경로</span>
    <select class="input" name="source">
      <?php foreach (['전화', '카톡', '숨고', '블로그', '기타'] as $s) : ?><option><?= $s ?></option><?php endforeach ?>
    </select>
  </label>
  <label class="field"><span>주소 <em>(선택)</em></span><input class="input" type="text" name="address" value="<?= esc(old('address')) ?>"></label>
  <label class="field"><span>통화 내용 <em>(선택)</em></span><textarea class="input" name="memo" rows="4"><?= esc(old('memo')) ?></textarea></label>

  <button class="btn btn--primary btn--lg">등록하기</button>
</form>
<?= $this->endSection() ?>
