<?= $this->extend('layouts/mobile') ?>

<?= $this->section('content') ?>
<div class="pad" style="padding: 24px 20px 0; gap: 16px">
  <h1 style="font-size: 22px; font-weight: 700">설정</h1>

  <section class="card stack" style="gap: 10px">
    <h2 class="section-title">업체 정보</h2>
    <dl class="kv">
      <dt>상호</dt><dd><?= esc(company('name')) ?></dd>
      <dt>문의 전화</dt><dd><?= company('phone') !== '' ? esc(company('phone')) : '<span class="warn-text">미설정 · 고객 견적 화면에 전화 안내가 안 나와요</span>' ?></dd>
      <dt>개인정보 보관</dt><dd><?= esc(company('privacyRetention')) ?></dd>
    </dl>
    <p class="t12 muted" style="margin: 0">바꾸려면 서버의 .env 파일에서 company.name, company.phone, company.privacyRetention 값을 고쳐 주세요.</p>
  </section>

  <section class="card stack" style="gap: 8px">
    <h2 class="section-title">PC 화면</h2>
    <a href="<?= site_url('leads/kanban') ?>" class="btn">문의 칸반</a>
    <a href="<?= site_url('prices') ?>" class="btn">단가표 관리</a>
  </section>

  <form method="post" action="<?= site_url('logout') ?>"><?= csrf_field() ?>
    <button class="btn btn--lg" style="width: 100%">로그아웃</button>
  </form>
</div>
<?= $this->endSection() ?>
