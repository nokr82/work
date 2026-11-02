<?= $this->extend('layouts/desktop') ?>

<?= $this->section('content') ?>
<?php $catId = $current ? (int) $current['id'] : 0; ?>
<div class="desktop__head">
  <div class="stack" style="gap: 4px">
    <h1>단가표</h1>
    <span class="t13 muted">공종 <?= count($categories) ?>개 · 품목 <?= $itemTotal ?>개<?= $lastEdit ? ' · 마지막 수정 ' . esc(short_date($lastEdit)) : '' ?></span>
  </div>
  <div class="desktop__tools">
    <a href="#new-item" class="btn btn--primary">+ 품목 추가</a>
  </div>
</div>

<div class="notice">단가를 바꿔도 <b>이미 보낸 견적의 금액은 바뀌지 않습니다.</b> 새로 만드는 견적부터 적용돼요.</div>
<?php foreach ($errors as $e) : ?><div class="flash flash--error"><?= esc($e) ?></div><?php endforeach ?>

<div class="price-layout">
  <section class="cat-list" aria-label="공종 목록">
    <?php foreach ($categories as $c) : ?>
      <a href="<?= site_url('prices?cat=' . $c['id']) ?>" class="cat-link"<?= (int) $c['id'] === $catId ? ' aria-pressed="true"' : '' ?>>
        <?= esc($c['name']) ?> <span class="t12 muted"><?= $c['item_count'] ?></span>
      </a>
    <?php endforeach ?>
    <form method="post" action="<?= site_url('prices/category') ?>" class="cat-add">
      <label class="sr-only" for="cat_name">새 공종 이름</label>
      <input id="cat_name" class="input" type="text" name="name" placeholder="+ 공종 추가" maxlength="30" required>
    </form>
  </section>

  <section class="table-wrap" aria-label="<?= $current ? esc($current['name']) : '' ?> 품목">
    <table>
      <thead>
        <tr><th>품목</th><th>단위</th><th class="num">재료비</th><th class="num">노무비</th><th class="num">단가 합계</th><th>사용</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it) :
            $on   = (bool) $it['enabled'];
            $edit = (int) $it['id'] === $editing;
            ?>
          <?php if ($edit) : ?>
            <tr class="editing">
              <td><input form="edit-form" class="cell-input" style="width: 100%" type="text" name="name" value="<?= esc($it['name']) ?>" aria-label="품목"></td>
              <td><input form="edit-form" class="cell-input" style="width: 56px; text-align: left" type="text" name="unit" value="<?= esc($it['unit']) ?>" aria-label="단위"></td>
              <td class="num"><input form="edit-form" class="cell-input" type="text" name="material" value="<?= won($it['material']) ?>" aria-label="재료비"></td>
              <td class="num"><input form="edit-form" class="cell-input" type="text" name="labor" value="<?= won($it['labor']) ?>" aria-label="노무비"></td>
              <td class="num bold"><?= won($it['material'] + $it['labor']) ?></td>
              <td></td>
              <td><button form="edit-form" class="btn btn--sm btn--primary">저장</button></td>
            </tr>
          <?php else : ?>
            <tr class="<?= $on ? '' : 'off' ?>">
              <td class="<?= $on ? 'bold' : '' ?>"><?= esc($it['name']) ?></td>
              <td><?= esc($it['unit']) ?></td>
              <td class="num"><?= won($it['material']) ?></td>
              <td class="num"><?= won($it['labor']) ?></td>
              <td class="num <?= $on ? 'bold' : '' ?>"><?= won($it['material'] + $it['labor']) ?></td>
              <td>
                <form method="post" action="<?= site_url('prices/item/' . $it['id'] . '/toggle') ?>">
                  <input type="checkbox" <?= $on ? 'checked' : '' ?> aria-label="<?= esc($it['name']) ?> 사용" onchange="this.form.submit()">
                </form>
              </td>
              <td><a class="t12" href="<?= site_url('prices?cat=' . $catId . '&edit=' . $it['id']) ?>">수정</a></td>
            </tr>
          <?php endif ?>
        <?php endforeach ?>
        <?php if ($current) : ?>
          <tr id="new-item">
            <td><input form="new-form" class="cell-input" style="width: 100%" type="text" name="name" placeholder="새 품목 이름" aria-label="새 품목 이름" required></td>
            <td><input form="new-form" class="cell-input" style="width: 56px; text-align: left" type="text" name="unit" placeholder="단위" aria-label="단위" required></td>
            <td class="num"><input form="new-form" class="cell-input" type="text" name="material" value="0" aria-label="재료비"></td>
            <td class="num"><input form="new-form" class="cell-input" type="text" name="labor" value="0" aria-label="노무비"></td>
            <td></td><td></td>
            <td><button form="new-form" class="btn btn--sm">추가</button></td>
          </tr>
        <?php endif ?>
      </tbody>
    </table>
    <?php /* 표 안의 입력칸은 form 속성으로 아래 폼들과 연결된다 */ ?>
    <form id="edit-form" method="post" action="<?= site_url('prices/item') ?>">
      <input type="hidden" name="id" value="<?= (int) $editing ?>"><input type="hidden" name="category_id" value="<?= $catId ?>">
    </form>
    <form id="new-form" method="post" action="<?= site_url('prices/item') ?>">
      <input type="hidden" name="category_id" value="<?= $catId ?>">
    </form>
  </section>
</div>
<?= $this->endSection() ?>
