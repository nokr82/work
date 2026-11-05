<?= $this->extend('layouts/mobile') ?>

<?= $this->section('content') ?>
<?php
// 검증 실패로 돌아왔으면 입력했던 값, 아니면 저장된 값
$val = static fn (string $k) => old($k) ?? (string) ($lead[$k] ?? '');
?>
<div class="top-header" style="padding: 12px 12px 12px">
  <div style="display: flex; align-items: center; gap: 4px">
    <a href="<?= site_url('leads/' . $lead['id']) ?>" class="back-link" aria-label="문의 상세로" style="color: var(--text)">
      <svg class="i" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
    </a>
    <h1 style="font-size: 18px; font-weight: 700">문의 정보 수정</h1>
  </div>
</div>

<form method="post" action="<?= site_url('leads/' . $lead['id'] . '/update') ?>" class="pad" style="gap: 16px"><?= csrf_field() ?>
  <?php foreach ($errors as $k => $e) : if ($k === 'delete') { continue; } ?><div class="flash flash--error"><?= esc($e) ?></div><?php endforeach ?>

  <label class="field"><span>이름</span><input class="input" type="text" name="name" value="<?= esc($val('name')) ?>" maxlength="50" required></label>
  <label class="field"><span>연락처</span><input class="input" type="tel" name="phone" value="<?= esc($val('phone')) ?>" placeholder="010-0000-0000" required></label>
  <label class="field"><span>문의 경로</span>
    <select class="input" name="source">
      <?php foreach ($sources as $s) : ?><option<?= $val('source') === $s ? ' selected' : '' ?>><?= esc($s) ?></option><?php endforeach ?>
    </select>
  </label>
  <label class="field"><span>주소 <em>(선택)</em></span><input class="input" type="text" name="address" value="<?= esc($val('address')) ?>" maxlength="200"></label>
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px">
    <label class="field"><span>공간 <em>(선택)</em></span>
      <select class="input" name="space_type">
        <option value="">선택 안 함</option>
        <?php foreach ($spaces as $s) : ?><option<?= $val('space_type') === $s ? ' selected' : '' ?>><?= esc($s) ?></option><?php endforeach ?>
      </select>
    </label>
    <label class="field"><span>평수 <em>(선택)</em></span><input class="input" type="number" name="size_py" min="1" max="999" value="<?= esc($val('size_py')) ?>"></label>
  </div>
  <label class="field"><span>공사 범위 <em>(쉼표로 구분)</em></span><input class="input" type="text" name="scopes" value="<?= esc(str_replace(',', ', ', $val('scopes'))) ?>" placeholder="예: 주방, 욕실" maxlength="255"></label>
  <label class="field"><span>예산 <em>(선택)</em></span><input class="input" type="text" name="budget" value="<?= esc($val('budget')) ?>" maxlength="50"></label>
  <label class="field"><span>희망 시기 <em>(선택)</em></span><input class="input" type="text" name="desired_time" value="<?= esc($val('desired_time')) ?>" maxlength="100"></label>
  <label class="field"><span>메모 <em>(선택)</em></span><textarea class="input" name="memo" rows="4"><?= esc($val('memo')) ?></textarea></label>

  <button class="btn btn--primary btn--lg">저장</button>
</form>

<div class="pad" style="padding-top: 0">
  <details class="card stack" style="gap: 10px"<?= isset($errors['delete']) ? ' open' : '' ?>>
    <summary class="t13 bold warn-text" style="cursor: pointer">이 문의 삭제</summary>
    <p class="t12 muted" style="margin: 0">견적(모든 버전)과 기록까지 함께 지워지고 되돌릴 수 없어요. 스팸·중복 문의를 정리할 때 쓰세요.</p>
    <?php if (isset($errors['delete'])) : ?><div class="flash flash--error"><?= esc($errors['delete']) ?></div><?php endif ?>
    <form method="post" action="<?= site_url('leads/' . $lead['id'] . '/delete') ?>" style="display: flex; gap: 8px"><?= csrf_field() ?>
      <label style="display: flex; flex-grow: 1; min-width: 0">
        <span class="sr-only">확인을 위해 고객 이름 입력</span>
        <input class="input" type="text" name="confirm_name" placeholder="확인: '<?= esc($lead['name']) ?>' 입력" required style="height: 44px">
      </label>
      <button class="btn" style="color: var(--warn); border-color: var(--warn)">삭제</button>
    </form>
  </details>
</div>
<?= $this->endSection() ?>
