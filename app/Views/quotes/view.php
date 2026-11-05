<?= $this->extend('layouts/mobile') ?>

<?= $this->section('content') ?>
<?php
$token    = $quote['token'];
$accepted = $lead['stage'] === 'contract';
$first    = true;
?>
<div class="stack" style="background: #fff; padding: 20px 20px 24px; gap: 18px">
  <div class="row">
    <div style="display: flex; align-items: center; gap: 10px">
      <div class="logo__mark"><?= esc(mb_substr(company('name'), 0, 1)) ?></div>
      <span style="font-size: 14px; font-weight: 700"><?= esc(company('name')) ?></span>
    </div>
    <a href="#" class="t13" style="text-decoration: none"
       onclick="document.querySelectorAll('.acc details').forEach(function (d) { d.open = true; }); window.print(); return false;">PDF 저장</a>
  </div>
  <div class="stack" style="gap: 6px">
    <span class="t13 muted"><?= esc($lead['name']) ?> 님<?= $lead['address'] ? ' · ' . esc($lead['address']) : '' ?> · 견적 v<?= $quote['version'] ?></span>
    <span style="font-size: 32px; font-weight: 700; letter-spacing: -0.5px"><?= man($totals['total']) ?></span>
    <span class="t13 muted">부가세 포함 · 공급가 <?= man($totals['supply']) ?> + 부가세 <?= man($totals['vat']) ?></span>
  </div>
  <div style="display: flex; gap: 8px; flex-wrap: wrap">
    <?php if ($quote['valid_until']) : ?><span class="badge badge--warn" style="padding: 4px 10px"><?= esc(short_date($quote['valid_until'])) ?>까지 유효<?= $expired ? ' (지남)' : '' ?></span><?php endif ?>
    <span class="badge badge--plain">공사 기간 약 <?= (int) $quote['work_weeks'] ?>주</span>
    <span class="badge badge--plain">하자 보수 1년</span>
  </div>
</div>

<div class="stack" style="padding: 16px 20px; gap: 8px">
  <?php if ($preview) : ?><div class="flash">사장님 미리보기 화면이에요. 열람 횟수에는 포함되지 않아요.</div><?php endif ?>
  <?php if ($flash) : ?><div class="flash"><?= esc($flash) ?></div><?php endif ?>
  <?php if ($replaced) : ?>
    <div class="flash flash--error">
      이 견적은 새 견적으로 바뀌었어요.
      <?php if ($latest) : ?><a href="<?= site_url('q/' . $latest['token']) ?>">최신 견적(v<?= (int) $latest['version'] ?>) 보기</a><?php endif ?>
    </div>
  <?php endif ?>
  <?php if ($expired && ! $replaced && ! $accepted) : ?>
    <div class="flash flash--error">유효기간이 지난 견적이에요. 자재값이 바뀌었을 수 있어서 바로 진행은 어렵고, 아래 "다시 견적 요청하기"로 알려 주시면 새 견적을 보내 드릴게요.</div>
  <?php endif ?>

  <h2 class="section-title" style="margin-bottom: 4px">공종별 금액</h2>
  <div class="acc">
    <?php foreach ($groups as $name => $g) : ?>
      <details<?= $first ? ' open' : '' ?>>
        <summary><span><?= esc($name) ?></span><b><?= man($g['total']) ?></b></summary>
        <ul>
          <?php foreach ($g['lines'] as $ln) : ?>
            <li><span class="muted"><?= esc($ln['name']) ?> <?= qty_label($ln['qty']) ?><?= esc($ln['unit']) ?></span><span><?= man(item_amount($ln)) ?></span></li>
          <?php endforeach ?>
        </ul>
      </details>
      <?php $first = false; ?>
    <?php endforeach ?>
  </div>
  <?php if ($quote['discount']) : ?><p class="t12 muted" style="margin: 4px 0 0">할인 −<?= man($quote['discount']) ?> 반영된 금액이에요.</p><?php endif ?>

  <?php if (! $preview && ! $accepted && ! $replaced) : ?>
    <details class="acc" style="padding: 0">
      <summary style="padding: 0 16px"><?= $expired ? '다시 견적 요청하기' : '수정을 요청할게요' ?></summary>
      <form method="post" action="<?= site_url('q/' . $token . '/revise') ?>" class="stack" style="padding: 0 16px 16px; gap: 8px"><?= csrf_field() ?>
        <textarea class="input" name="note" rows="3" maxlength="500" placeholder="바꾸고 싶은 부분을 알려 주세요" required></textarea>
        <button class="btn btn--sm">수정 요청 보내기</button>
      </form>
    </details>
  <?php endif ?>
</div>

<div class="footer-bar" style="padding: 14px 20px 28px; gap: 10px">
  <?php if ($phone !== '') : ?>
    <span class="t12 muted" style="text-align: center">궁금한 점은 <a href="tel:<?= esc($phone) ?>"><?= esc($phone) ?></a> 으로 편하게 전화 주세요</span>
  <?php endif ?>
  <?php if ($accepted) : ?>
    <div class="btn btn--lg" style="color: var(--ok); border-color: var(--ok)">진행 요청이 접수되었어요</div>
  <?php elseif ($preview || $replaced || $expired) : ?>
    <button class="btn btn--primary btn--lg" disabled>이 견적으로 진행할게요</button>
  <?php else : ?>
    <form method="post" action="<?= site_url('q/' . $token . '/accept') ?>" style="display: flex"><?= csrf_field() ?>
      <button class="btn btn--primary btn--lg btn--grow">이 견적으로 진행할게요</button>
    </form>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
