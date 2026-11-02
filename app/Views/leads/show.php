<?= $this->extend('layouts/mobile') ?>

<?= $this->section('content') ?>
<?php
$stages  = ['new', 'consulting', 'measure', 'quoted', 'contract'];
$barText = ['new' => '신규', 'consulting' => '상담', 'measure' => '실측', 'quoted' => '견적 발송', 'contract' => '계약'];
$current = (int) array_search($lead['stage'], $stages, true);
$phone   = $lead['phone'];
$meta    = [];
if (! empty($lead['address'])) { $meta[] = ['주소', $lead['address']]; }
if (! empty($lead['size_py']) || ! empty($lead['space_type'])) {
    $meta[] = ['평수', trim(($lead['size_py'] ? $lead['size_py'] . '평' : '') . ($lead['space_type'] ? ' · ' . $lead['space_type'] : ''), ' ·')];
}
if (! empty($lead['scopes'])) { $meta[] = ['범위', str_replace(',', ', ', $lead['scopes'])]; }
if (! empty($lead['budget'])) { $meta[] = ['예산', $lead['budget']]; }
if (! empty($lead['desired_time'])) { $meta[] = ['희망 시기', $lead['desired_time']]; }
if (! empty($lead['measure_at'])) { $meta[] = ['실측', when_label($lead['measure_at'])]; }
if (! empty($lead['memo'])) { $meta[] = ['메모', $lead['memo']]; }
?>
<div class="top-header" style="padding: 12px 12px 16px; gap: 12px">
  <div style="display: flex; align-items: center; gap: 4px">
    <a href="<?= site_url('leads') ?>" class="back-link" aria-label="문의 목록으로" style="color: var(--text)">
      <svg class="i" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
    </a>
    <div class="stack">
      <h1 style="font-size: 20px; font-weight: 700"><?= esc($lead['name']) ?> 님</h1>
      <span class="t12 muted"><?= esc(mask_phone($phone)) ?> · <?= esc($lead['source']) ?> 문의 · <?= esc(short_date($lead['created_at'])) ?></span>
    </div>
  </div>

  <ol aria-label="진행 단계" class="progress" style="list-style: none; margin: 0; padding: 0 8px; grid-template-columns: repeat(5, minmax(0, 1fr))">
    <?php foreach ($stages as $i => $s) : ?>
      <li<?= $i === $current ? ' aria-current="step"' : '' ?> class="stack" style="gap: 6px">
        <span class="progress__bar<?= $i <= $current ? ' progress__bar--on' : '' ?>"></span>
        <span class="t11 <?= $i === $current ? 'primary-text' : 'muted' ?>"><?= esc($barText[$s]) ?></span>
      </li>
    <?php endforeach ?>
  </ol>

  <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; padding: 0 8px">
    <a href="tel:<?= esc($phone) ?>" class="btn btn--primary">전화</a>
    <a href="sms:<?= esc($phone) ?>" class="btn">문자</a>
  </div>
  <form method="post" action="<?= site_url('leads/' . $lead['id'] . '/stage') ?>" style="display: flex; gap: 8px; padding: 0 8px">
    <label class="sr-only" for="stage">단계 변경</label>
    <select id="stage" name="stage" class="input" style="height: 44px; flex-grow: 1">
      <?php foreach (stage_labels() as $key => $label) : ?>
        <option value="<?= $key ?>"<?= $key === $lead['stage'] ? ' selected' : '' ?>><?= esc($label) ?></option>
      <?php endforeach ?>
    </select>
    <button class="btn">단계 변경</button>
  </form>
</div>

<div class="pad">
  <section class="card stack" style="gap: 10px">
    <div class="row">
      <h2 class="section-title">견적</h2>
      <a href="<?= site_url('quotes/new/' . $lead['id']) ?>" class="t13 bold" style="text-decoration: none">새 버전 만들기</a>
    </div>
    <?php if (! $quotes) : ?><p class="t13 muted" style="margin: 0">아직 만든 견적이 없어요.</p><?php endif ?>
    <?php foreach ($quotes as $i => $qt) :
        $isLatest = $i === 0;
        ?>
      <a href="<?= site_url('quotes/edit/' . $qt['id']) ?>" class="row" style="<?= $isLatest ? 'border: 1.5px solid var(--warn-border); background: var(--warn-tint);' : 'border: 1px solid var(--border);' ?> border-radius: 10px; padding: 12px; text-decoration: none; color: var(--text)">
        <div class="stack" style="gap: 3px">
          <span style="font-size: 14px; font-weight: <?= $isLatest ? 700 : 400 ?>">v<?= $qt['version'] ?> · <?= man($qt['totals']['total']) ?><?= $qt['status'] === 'draft' ? ' (작성 중)' : '' ?></span>
          <?php if ($qt['status'] === 'sent') : ?>
            <span class="t12 <?= $isLatest && $qt['view_count'] ? 'warn-text' : 'muted' ?>">
              <?php if ($qt['view_count']) : ?>열람 <?= $qt['view_count'] ?>회 · 마지막 <?= esc(time_ago($qt['last_viewed_at'])) ?><?php else : ?>미열람 · <?= esc(time_ago($qt['sent_at'])) ?> 발송<?php endif ?>
            </span>
          <?php endif ?>
          <?php if (! empty($qt['revision_note'])) : ?><span class="t12 muted">수정 요청: <?= esc($qt['revision_note']) ?></span><?php endif ?>
        </div>
        <svg class="i" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" style="color: var(--muted)" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
      </a>
    <?php endforeach ?>
  </section>

  <section class="card stack" style="gap: 10px">
    <h2 class="section-title">공사 정보</h2>
    <?php if ($meta) : ?>
      <dl class="kv">
        <?php foreach ($meta as [$k, $v]) : ?><dt><?= esc($k) ?></dt><dd><?= esc($v) ?></dd><?php endforeach ?>
      </dl>
    <?php else : ?>
      <p class="t13 muted" style="margin: 0">입력된 정보가 없어요.</p>
    <?php endif ?>
    <?php if ((int) $lead['photo_count'] > 0) : ?>
      <div class="t12 muted">현장 사진 <?= (int) $lead['photo_count'] ?>장</div>
    <?php endif ?>
  </section>

  <section class="card stack" style="gap: 10px">
    <h2 class="section-title">기록</h2>
    <ul class="t13" style="list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px">
      <?php foreach ($logs as $log) : ?>
        <li style="display: flex; gap: 10px"><span class="muted" style="width: 64px; flex-shrink: 0"><?= esc(time_ago($log['created_at'])) ?></span><span><?= esc($log['message']) ?></span></li>
      <?php endforeach ?>
    </ul>
    <form method="post" action="<?= site_url('leads/' . $lead['id'] . '/memo') ?>" style="display: flex; gap: 8px">
      <label style="display: flex; flex-grow: 1">
        <span class="sr-only">메모 추가</span>
        <input type="text" name="memo" class="text-input" placeholder="통화 내용이나 메모 남기기" maxlength="250">
      </label>
      <button class="btn">저장</button>
    </form>
  </section>
</div>
<?= $this->endSection() ?>
