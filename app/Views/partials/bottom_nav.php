<?php
// 모바일 하단 탭. $nav: home | leads | quote | settings
$cls = static fn (string $key): string => $nav === $key ? ' class="active"' : '';
?>
<nav class="bottom-nav" aria-label="하단 메뉴">
  <a href="<?= site_url('/') ?>"<?= $cls('home') ?>><svg class="i" width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>홈</a>
  <a href="<?= site_url('leads') ?>"<?= $cls('leads') ?>><svg class="i" width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 14h8"/></svg>문의</a>
  <a href="<?= site_url('quotes/edit') ?>"<?= $cls('quote') ?>><svg class="i" width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></svg>견적</a>
  <a href="<?= site_url('settings') ?>"<?= $cls('settings') ?>><svg class="i" width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1 7 17M17 7l2.1-2.1"/></svg>설정</a>
</nav>
