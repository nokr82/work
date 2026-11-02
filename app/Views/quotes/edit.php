<?= $this->extend('layouts/mobile') ?>

<?= $this->section('content') ?>
<?php $base = site_url('quotes/' . $quote['id']); ?>
<div class="top-header" style="padding: 12px 12px 0; gap: 10px">
  <div style="display: flex; align-items: center; gap: 4px">
    <a href="<?= site_url('leads/' . $quote['lead_id']) ?>" class="back-link" aria-label="문의 상세로" style="color: var(--text)">
      <svg class="i" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
    </a>
    <div class="stack">
      <h1 style="font-size: 18px; font-weight: 700">견적 v<?= $quote['version'] ?> <?= $locked ? '(발송됨)' : '작성' ?></h1>
      <span class="t12 muted"><?= esc($lead['name']) ?> 님<?= $prevVersion ? ' · v' . $prevVersion . '에서 복사함' : '' ?></span>
    </div>
  </div>
  <div role="tablist" aria-label="공종" class="tabs">
    <?php foreach ($cats as $name => $count) : ?>
      <a href="<?= site_url('quotes/edit/' . $quote['id']) . '?cat=' . rawurlencode($name) ?>" role="tab" aria-selected="<?= $name === $current ? 'true' : 'false' ?>">
        <?= esc($name) ?><?php if ($count) : ?> <span class="t11"><?= $count ?></span><?php endif ?>
      </a>
    <?php endforeach ?>
  </div>
</div>

<div class="pad" style="gap: 8px">
  <?php if ($flash) : ?><div class="flash"><?= esc($flash) ?></div><?php endif ?>
  <?php if ($locked) : ?>
    <div class="flash">
      <div>고객 링크 (발송됨)</div>
      <input class="input" type="text" value="<?= esc($shareUrl) ?>" readonly onfocus="this.select()" style="margin-top: 6px; height: 40px">
      <div class="t12 muted" style="margin-top: 6px">발송한 견적은 수정할 수 없어요. 고치려면 문의 상세에서 새 버전을 만들어 주세요.</div>
    </div>
  <?php endif ?>

  <div class="row">
    <span class="t13 muted"><?= esc($current) ?> 소계 <b style="color: var(--text)"><?= man($subtotal) ?></b></span>
    <?php if (! $locked) : ?>
      <details class="add-menu">
        <summary class="btn btn--sm btn--dashed">+ 단가표에서 추가</summary>
        <form method="post" action="<?= $base ?>/items" class="add-menu__body">
          <select class="input" name="price_item_id" aria-label="추가할 품목" style="height: 40px">
            <?php foreach ($addable as $p) : ?>
              <option value="<?= $p['id'] ?>"><?= esc($p['name']) ?> (<?= won($p['material'] + $p['labor']) ?>/<?= esc($p['unit']) ?>)</option>
            <?php endforeach ?>
          </select>
          <button class="btn btn--primary btn--sm"<?= $addable ? '' : ' disabled' ?>>추가</button>
        </form>
      </details>
    <?php endif ?>
  </div>

  <?php if (! $items) : ?><div class="card t13 muted" style="text-align: center">이 공종에 담긴 품목이 없어요.</div><?php endif ?>

  <?php foreach ($items as $it) :
      $amount = item_amount($it);
      $key    = $it['category'] . '|' . $it['name'];
      $diff   = isset($prev[$key]) ? $amount - $prev[$key] : null;
      ?>
    <div class="card stack" style="gap: 10px">
      <div class="row" style="align-items: flex-start; gap: 8px">
        <div class="stack" style="gap: 2px">
          <span class="t15 bold"><?= esc($it['name']) ?></span>
          <span class="t12 muted">재료 <?= won($it['material']) ?> + 노무 <?= won($it['labor']) ?> / <?= esc($it['unit']) ?></span>
        </div>
        <span class="t15 bold" style="white-space: nowrap"><?= man($amount) ?></span>
      </div>
      <div style="display: flex; align-items: center; gap: 8px">
        <?php if ($locked) : ?>
          <span class="t15 bold"><?= qty_label($it['qty']) ?> <?= esc($it['unit']) ?></span>
        <?php else : ?>
          <form method="post" action="<?= $base ?>/items/<?= $it['id'] ?>/qty" style="display: flex; align-items: center; gap: 8px">
            <button name="delta" value="-1" class="btn" aria-label="수량 줄이기" style="width: 44px; height: 40px; border-radius: 8px; font-size: 18px; padding: 0">−</button>
            <span class="t15 bold" style="min-width: 64px; text-align: center"><?= qty_label($it['qty']) ?> <?= esc($it['unit']) ?></span>
            <button name="delta" value="1" class="btn" aria-label="수량 늘리기" style="width: 44px; height: 40px; border-radius: 8px; font-size: 18px; padding: 0">+</button>
          </form>
        <?php endif ?>
        <?php if ($diff !== null && $diff !== 0) : ?>
          <span class="badge badge--warn" style="margin-left: auto">v<?= $prevVersion ?> 대비 <?= $diff < 0 ? '−' : '+' ?><?= number_format((int) round(abs($diff) / 10000)) ?>만 원</span>
        <?php endif ?>
      </div>
    </div>
  <?php endforeach ?>
</div>

<div class="footer-bar" style="padding: 14px 20px 24px">
  <dl class="t13" style="margin: 0; display: grid; grid-template-columns: minmax(0, 1fr) auto; row-gap: 4px">
    <dt class="muted">공급가<?= $quote['discount'] ? ' (할인 −' . man($quote['discount']) . ' 반영)' : '' ?></dt><dd style="margin: 0; text-align: right"><?= man($totals['supply']) ?></dd>
    <dt class="muted">부가세</dt><dd style="margin: 0; text-align: right"><?= man($totals['vat']) ?></dd>
    <dt class="t15 bold">총액</dt><dd style="margin: 0; text-align: right; font-size: 20px; font-weight: 700"><?= man($totals['total']) ?></dd>
  </dl>
  <?php if (! $locked) : ?>
    <form method="post" action="<?= $base ?>/discount" style="display: flex; align-items: center; gap: 8px">
      <input type="hidden" name="cat" value="<?= esc($current) ?>">
      <label class="t12 muted" for="discount_man">할인</label>
      <input class="input" id="discount_man" type="number" min="0" name="discount_man" value="<?= (int) round($quote['discount'] / 10000) ?>" style="height: 36px; width: 90px">
      <span class="t12 muted">만 원</span>
      <button class="btn btn--sm">적용</button>
    </form>
  <?php endif ?>
  <div style="display: flex; gap: 8px">
    <a href="<?= site_url('q/' . $quote['token']) ?>?preview=1" target="_blank" rel="noopener" class="btn" style="height: 48px; padding: 0 18px; font-size: 15px">고객 화면 미리보기</a>
    <?php if (! $locked) : ?>
      <form method="post" action="<?= $base ?>/send" style="display: flex; flex-grow: 1">
        <button class="btn btn--primary btn--grow" style="height: 48px; font-size: 15px">링크로 발송</button>
      </form>
    <?php endif ?>
  </div>
</div>
<?= $this->endSection() ?>
