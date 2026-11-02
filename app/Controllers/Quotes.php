<?php

namespace App\Controllers;

use App\Models\LeadLogModel;
use App\Models\LeadModel;
use App\Models\PriceCategoryModel;
use App\Models\PriceItemModel;
use App\Models\QuoteItemModel;
use App\Models\QuoteModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Quotes extends AppBase
{
    // 하단 '견적' 탭: 가장 최근에 손댄 견적으로, 없으면 문의 목록으로
    public function latest()
    {
        $q = (new QuoteModel())->orderBy('updated_at', 'DESC')->first();

        return redirect()->to($q ? site_url('quotes/edit/' . $q['id']) : site_url('leads'));
    }

    // 문의에 새 견적 버전을 만든다 (이전 버전 품목을 복사)
    public function create(string $leadId)
    {
        $lead = (new LeadModel())->find((int) $leadId);
        if (! $lead) {
            throw PageNotFoundException::forPageNotFound();
        }
        $id = (new QuoteModel())->createDraft((int) $lead['id']);

        return redirect()->to(site_url('quotes/edit/' . $id));
    }

    // 견적 작성 화면
    public function edit(string $id): string
    {
        $quote = $this->findQuote($id);
        $lead  = (new LeadModel())->find($quote['lead_id']);
        $items = (new QuoteItemModel())->forQuote((int) $quote['id']);

        // 직전 버전의 같은 품목 금액과 비교해 "v2 대비" 표시
        $prev = [];
        if ((int) $quote['version'] > 1) {
            $prevQuote = (new QuoteModel())->where('lead_id', $quote['lead_id'])->where('version <', $quote['version'])->orderBy('version', 'DESC')->first();
            if ($prevQuote) {
                foreach ((new QuoteItemModel())->forQuote((int) $prevQuote['id']) as $p) {
                    $prev[$p['category'] . '|' . $p['name']] = item_amount($p);
                }
                $prevVersion = $prevQuote['version'];
            }
        }

        // 공종 탭: 견적에 쓰인 공종 + 단가표의 공종
        $cats = [];
        foreach ($items as $it) {
            $cats[$it['category']] = ($cats[$it['category']] ?? 0) + 1;
        }
        $priceCats = (new PriceCategoryModel())->orderBy('sort_order', 'ASC')->findAll();
        foreach ($priceCats as $pc) {
            $cats[$pc['name']] = $cats[$pc['name']] ?? 0;
        }
        $current = (string) ($this->request->getGet('cat') ?: (array_keys(array_filter($cats))[0] ?? array_key_first($cats)));

        $catItems = array_values(array_filter($items, static function ($it) use ($current) {
            return $it['category'] === $current;
        }));
        $subtotal = array_sum(array_map('item_amount', $catItems));

        // 이 공종에서 추가할 수 있는 단가표 품목
        $addable = [];
        foreach ($priceCats as $pc) {
            if ($pc['name'] === $current) {
                $addable = (new PriceItemModel())->inCategory((int) $pc['id'], true);
            }
        }

        return view('quotes/edit', [
            'title'       => '견적 작성',
            'quote'       => $quote,
            'lead'        => $lead,
            'cats'        => $cats,
            'current'     => $current,
            'items'       => $catItems,
            'subtotal'    => $subtotal,
            'addable'     => $addable,
            'prev'        => $prev,
            'prevVersion' => $prevVersion ?? null,
            'totals'      => quote_totals($items, (int) $quote['discount']),
            'shareUrl'    => site_url('q/' . $quote['token']),
            'locked'      => $quote['status'] !== 'draft',
            'flash'       => session()->getFlashdata('message'),
        ]);
    }

    // 단가표에서 품목 추가 (단가를 복사해 저장)
    public function addItem(string $id)
    {
        $quote = $this->findDraft($id);
        $price = (new PriceItemModel())->find((int) $this->request->getPost('price_item_id'));
        if ($price) {
            $cat   = (new PriceCategoryModel())->find($price['category_id']);
            $items = new QuoteItemModel();
            $items->insert([
                'quote_id'   => $quote['id'],
                'category'   => $cat['name'],
                'name'       => $price['name'],
                'unit'       => $price['unit'],
                'qty'        => 1,
                'material'   => $price['material'],
                'labor'      => $price['labor'],
                'sort_order' => count($items->forQuote((int) $quote['id'])),
            ]);
            $this->touch($quote);
        }

        return redirect()->to($this->back($quote, $cat['name'] ?? null));
    }

    // 수량 증감 (0이 되면 삭제)
    public function qty(string $id, string $itemId)
    {
        $quote = $this->findDraft($id);
        $items = new QuoteItemModel();
        $item  = $items->where('quote_id', $quote['id'])->find((int) $itemId);
        if ($item) {
            $qty = (float) $item['qty'] + (float) $this->request->getPost('delta');
            if ($qty <= 0) {
                $items->delete($item['id']);
            } else {
                $items->update($item['id'], ['qty' => $qty]);
            }
            $this->touch($quote);
        }

        return redirect()->to($this->back($quote, $item['category'] ?? null));
    }

    // 할인액 변경 (만 원 단위 입력)
    public function discount(string $id)
    {
        $quote    = $this->findDraft($id);
        $discount = max(0, (int) $this->request->getPost('discount_man')) * 10000;
        (new QuoteModel())->update($quote['id'], ['discount' => $discount]);

        return redirect()->to($this->back($quote, $this->request->getPost('cat')));
    }

    // 링크로 발송: 상태를 '발송'으로 바꾸고 문의 단계를 '견적 발송'으로
    public function send(string $id)
    {
        $quote = $this->findDraft($id);
        $items = (new QuoteItemModel())->forQuote((int) $quote['id']);
        if (! $items) {
            return redirect()->to($this->back($quote))->with('message', '품목을 먼저 추가해 주세요.');
        }

        (new QuoteModel())->update($quote['id'], [
            'status'      => 'sent',
            'sent_at'     => date('Y-m-d H:i:s'),
            'valid_until' => date('Y-m-d', strtotime('+14 days')),
        ]);
        $leads = new LeadModel();
        $lead  = $leads->find($quote['lead_id']);
        if ($lead && in_array($lead['stage'], ['new', 'consulting', 'measure'], true)) {
            $leads->update($lead['id'], ['stage' => 'quoted']);
        }
        (new LeadLogModel())->add((int) $quote['lead_id'], '견적 v' . $quote['version'] . ' 발송');

        return redirect()->to($this->back($quote))->with('message', '발송했어요. 아래 링크를 고객에게 보내 주세요.');
    }

    // ---------- 고객용 (로그인 없음) ----------

    // 고객이 링크로 견적을 본다. 열람 횟수와 시각을 기록한다.
    public function publicView(string $token): string
    {
        $quote = (new QuoteModel())->findByToken($token);
        $preview = (bool) $this->request->getGet('preview');
        if (! $quote || ($quote['status'] !== 'sent' && ! $preview)) {
            throw PageNotFoundException::forPageNotFound('견적을 찾을 수 없습니다.');
        }

        // 진행 요청·수정 요청 후 되돌아온 화면은 열람으로 세지 않는다
        $flash = session()->getFlashdata('message');
        if (! $preview && ! $flash) {
            $count = (int) $quote['view_count'] + 1;
            (new QuoteModel())->update($quote['id'], ['view_count' => $count, 'last_viewed_at' => date('Y-m-d H:i:s')]);
            (new LeadLogModel())->add((int) $quote['lead_id'], '고객이 견적 v' . $quote['version'] . '를 열람 (' . $count . '번째)');
            $quote['view_count'] = $count;
        }

        $lead   = (new LeadModel())->find($quote['lead_id']);
        $items  = (new QuoteItemModel())->forQuote((int) $quote['id']);
        $groups = [];
        foreach ($items as $it) {
            $groups[$it['category']]['lines'][] = $it;
            $groups[$it['category']]['total']   = ($groups[$it['category']]['total'] ?? 0) + item_amount($it);
        }

        return view('quotes/view', [
            'title'   => '견적 확인',
            'quote'   => $quote,
            'lead'    => $lead,
            'groups'  => $groups,
            'totals'  => quote_totals($items, (int) $quote['discount']),
            'phone'   => '02-000-0000',
            'flash'   => $flash,
            'preview' => $preview,
        ]);
    }

    public function accept(string $token)
    {
        $quote = $this->findSent($token);
        $leads = new LeadModel();
        $lead  = $leads->find($quote['lead_id']);
        if ($lead && $lead['stage'] !== 'contract') {
            $leads->update($lead['id'], ['stage' => 'contract']);
            (new LeadLogModel())->add((int) $lead['id'], '고객이 견적 v' . $quote['version'] . '로 진행을 요청 → 계약');
        }

        return redirect()->to(site_url('q/' . $token))->with('message', '진행 요청이 접수되었어요. 곧 연락드릴게요.');
    }

    public function revise(string $token)
    {
        $quote = $this->findSent($token);
        $note  = trim((string) $this->request->getPost('note'));
        if ($note !== '') {
            $note = mb_substr($note, 0, 500);
            (new QuoteModel())->update($quote['id'], ['revision_note' => $note]);
            (new LeadLogModel())->add((int) $quote['lead_id'], '견적 v' . $quote['version'] . ' 수정 요청: ' . mb_substr($note, 0, 150));
        }

        return redirect()->to(site_url('q/' . $token))->with('message', '수정 요청을 보냈어요.');
    }

    // ---------- 내부 ----------

    private function findQuote(string $id): array
    {
        $quote = (new QuoteModel())->find((int) $id);
        if (! $quote) {
            throw PageNotFoundException::forPageNotFound('견적을 찾을 수 없습니다.');
        }

        return $quote;
    }

    // 발송 전(초안) 견적만 고칠 수 있다
    private function findDraft(string $id): array
    {
        $quote = $this->findQuote($id);
        if ($quote['status'] !== 'draft') {
            throw PageNotFoundException::forPageNotFound('이미 발송한 견적은 수정할 수 없습니다. 새 버전을 만들어 주세요.');
        }

        return $quote;
    }

    private function findSent(string $token): array
    {
        $quote = (new QuoteModel())->findByToken($token);
        if (! $quote || $quote['status'] !== 'sent') {
            throw PageNotFoundException::forPageNotFound();
        }

        return $quote;
    }

    private function touch(array $quote): void
    {
        // 같은 값으로 갱신하면 모델이 updated_at을 새로 채운다
        (new QuoteModel())->update($quote['id'], ['discount' => $quote['discount']]);
    }

    private function back(array $quote, ?string $cat = null): string
    {
        return site_url('quotes/edit/' . $quote['id']) . ($cat ? '?cat=' . rawurlencode($cat) : '');
    }
}
