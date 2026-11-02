<?= $this->extend('layouts/mobile') ?>

<?= $this->section('content') ?>
<?php
$total = count($steps);
$v     = static function (string $k, $default = '') use ($data) {
    return $data[$k] ?? $default;
};
$titles = [
    1 => '연락받을 분의<br>정보를 알려 주세요',
    2 => '어떤 공사를<br>생각하고 계세요?',
    3 => '예산과 시기를<br>알려 주세요',
    4 => '이대로<br>접수할까요?',
];
$nextLabel = [1 => '다음: 공사 범위', 2 => '다음: 예산과 시기', 3 => '다음: 확인'];
?>
<div class="stack" style="padding: 20px 20px 0; gap: 16px">
  <div style="display: flex; align-items: center; gap: 10px">
    <div class="logo__mark" style="width: 36px; height: 36px; font-size: 15px">한</div>
    <div class="stack">
      <span class="t15 bold">한빛인테리어</span>
      <span class="t12 muted">무료 상담 · 보통 2시간 안에 연락드려요</span>
    </div>
  </div>
  <div class="stack" style="gap: 8px">
    <div role="progressbar" aria-valuemin="1" aria-valuemax="<?= $total ?>" aria-valuenow="<?= $step ?>" aria-label="<?= $total ?>단계 중 <?= $step ?>단계" class="progress" style="grid-template-columns: repeat(<?= $total ?>, minmax(0, 1fr))">
      <?php for ($i = 1; $i <= $total; $i++) : ?>
        <span class="progress__bar<?= $i <= $step ? ' progress__bar--on' : '' ?>"></span>
      <?php endfor ?>
    </div>
    <span class="t12 muted"><?= $step ?> / <?= $total ?> · <?= esc($steps[$step]) ?></span>
  </div>
</div>

<?php $action = $step < 4 ? site_url('inquiry/step/' . $step) : site_url('inquiry/submit'); ?>
<form id="inquiry" method="post" action="<?= $action ?>" style="flex-grow: 1; padding: 20px; display: flex; flex-direction: column; gap: 28px">
  <h1 style="font-size: 22px; font-weight: 700; line-height: 1.4"><?= $titles[$step] ?></h1>
  <?php foreach ($errors as $e) : ?><div class="flash flash--error"><?= esc($e) ?></div><?php endforeach ?>

  <?php if ($step === 1) : ?>
    <label class="field"><span>이름</span><input class="input" type="text" name="name" value="<?= esc($v('name')) ?>" required></label>
    <label class="field"><span>연락처</span><input class="input" type="tel" name="phone" value="<?= esc($v('phone')) ?>" placeholder="010-0000-0000" required></label>

  <?php elseif ($step === 2) : ?>
    <fieldset class="fieldset">
      <legend>공간 유형</legend>
      <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px">
        <?php foreach ($spaces as $s) : ?>
          <label class="choice choice--box"><input type="radio" name="space" value="<?= esc($s) ?>"<?= $s === $v('space', '아파트') ? ' checked' : '' ?>><?= esc($s) ?></label>
        <?php endforeach ?>
      </div>
    </fieldset>

    <label class="stack" style="gap: 10px">
      <span style="font-size: 14px; font-weight: 700">평수</span>
      <span style="height: 48px; border-radius: 10px; border: 1px solid var(--border); display: flex; align-items: center; padding: 0 14px; gap: 8px">
        <input type="number" name="size" min="1" max="999" value="<?= esc($v('size')) ?>" style="flex-grow: 1; min-width: 0; border: none; font-size: 16px; color: var(--text); outline: none">
        <span class="muted" style="font-size: 14px">평</span>
      </span>
    </label>

    <fieldset class="fieldset">
      <legend>공사 범위 <span class="muted" style="font-weight: 400">(여러 개 선택)</span></legend>
      <div style="display: flex; flex-wrap: wrap; gap: 8px">
        <?php foreach ($scopes as $s) : ?>
          <label class="choice choice--pill"><input type="checkbox" name="scopes[]" value="<?= esc($s) ?>"<?= in_array($s, (array) $v('scopes', []), true) ? ' checked' : '' ?>><?= esc($s) ?></label>
        <?php endforeach ?>
      </div>
    </fieldset>

  <?php elseif ($step === 3) : ?>
    <label class="field"><span>공사할 곳 주소 <em>(선택)</em></span><input class="input" type="text" name="address" value="<?= esc($v('address')) ?>" placeholder="예: 서울 성동구 행당동"></label>
    <label class="field"><span>예산</span>
      <select class="input" name="budget">
        <option value="">선택 안 함</option>
        <?php foreach ($budgets as $b) : ?><option<?= $b === $v('budget') ? ' selected' : '' ?>><?= esc($b) ?></option><?php endforeach ?>
      </select>
    </label>
    <label class="field"><span>희망 시기 <em>(선택)</em></span><input class="input" type="text" name="desired_time" value="<?= esc($v('desired_time')) ?>" placeholder="예: 12월 중순 착공"></label>
    <label class="field"><span>전하고 싶은 말 <em>(선택)</em></span><textarea class="input" name="memo" rows="3"><?= esc($v('memo')) ?></textarea></label>

  <?php else : ?>
    <dl class="kv" style="row-gap: 12px">
      <dt>이름</dt><dd><?= esc($v('name')) ?></dd>
      <dt>연락처</dt><dd><?= esc($v('phone')) ?></dd>
      <dt>공간</dt><dd><?= esc($v('space')) ?><?= $v('size') ? ' · ' . esc($v('size')) . '평' : '' ?></dd>
      <dt>범위</dt><dd><?= esc(implode(', ', (array) $v('scopes', [])) ?: '-') ?></dd>
      <dt>주소</dt><dd><?= esc($v('address') ?: '-') ?></dd>
      <dt>예산</dt><dd><?= esc($v('budget') ?: '-') ?></dd>
      <dt>희망 시기</dt><dd><?= esc($v('desired_time') ?: '-') ?></dd>
    </dl>
  <?php endif ?>
</form>

<div style="padding: 12px 20px 28px; display: flex; gap: 8px">
  <?php if ($step > 1) : ?>
    <a href="<?= site_url('inquiry?step=' . ($step - 1)) ?>" class="btn btn--lg" style="padding: 0 20px; font-size: 15px">이전</a>
  <?php endif ?>
  <button form="inquiry" class="btn btn--primary btn--lg btn--grow"><?= $step < 4 ? esc($nextLabel[$step]) : '문의 접수하기' ?></button>
</div>
<?= $this->endSection() ?>
