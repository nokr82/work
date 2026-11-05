<?php

namespace App\Models;

use CodeIgniter\Model;

class QuoteModel extends Model
{
    protected $table         = 'quotes';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'lead_id', 'version', 'token', 'status', 'discount', 'work_weeks', 'valid_until',
        'sent_at', 'view_count', 'last_viewed_at', 'revision_note',
    ];

    // 한 문의의 견적들 (최신 버전이 먼저) + 합계
    public function forLead(int $leadId): array
    {
        $quotes = $this->where('lead_id', $leadId)->orderBy('version', 'DESC')->findAll();
        $items  = new QuoteItemModel();
        foreach ($quotes as &$q) {
            $q['totals'] = quote_totals($items->forQuote((int) $q['id']), (int) $q['discount']);
        }
        unset($q);

        return $quotes;
    }

    public function findByToken(string $token): ?array
    {
        return $this->where('token', $token)->first();
    }

    // 상태: draft(작성 중) → sent(발송) → replaced(새 버전 발송으로 대체됨)
    public const STATUS_LABELS = ['draft' => '작성 중', 'sent' => '발송됨', 'replaced' => '이전 버전'];

    // 유효기간(valid_until 당일까지)이 지났는지
    public static function isExpired(array $quote): bool
    {
        return ! empty($quote['valid_until']) && $quote['valid_until'] < date('Y-m-d');
    }

    // 발송한 적이 있는 견적 수 (작성 중 초안은 세지 않는다)
    public function sentCount(int $leadId): int
    {
        return $this->where('lead_id', $leadId)->where('status !=', 'draft')->countAllResults();
    }

    // 이 문의에 작성 중인 초안
    public function draftFor(int $leadId): ?array
    {
        return $this->where('lead_id', $leadId)->where('status', 'draft')->orderBy('version', 'DESC')->first();
    }

    // 새 버전을 보내면 이전에 보낸 버전들은 '대체됨'으로 바꿔 고객이 옛 금액으로 진행하지 못하게 한다
    public function replaceOlder(int $leadId, int $exceptId): void
    {
        $this->builder()
            ->where('lead_id', $leadId)->where('status', 'sent')->where('id !=', $exceptId)
            ->update(['status' => 'replaced']);
    }

    // 고객이 지금 진행할 수 있는 최신 발송본
    public function latestSent(int $leadId): ?array
    {
        return $this->where('lead_id', $leadId)->where('status', 'sent')->orderBy('version', 'DESC')->first();
    }

    // 새 초안 만들기: 가장 최근 버전의 품목을 복사한다. 작성 중인 초안이 있으면 그것을 그대로 쓴다.
    public function createDraft(int $leadId): int
    {
        $draft = $this->draftFor($leadId);
        if ($draft) {
            return (int) $draft['id'];
        }

        $last = $this->where('lead_id', $leadId)->orderBy('version', 'DESC')->first();

        $quoteId = $this->insert([
            'lead_id'    => $leadId,
            'version'    => $last ? (int) $last['version'] + 1 : 1,
            'token'      => bin2hex(random_bytes(10)),
            'status'     => 'draft',
            'discount'   => $last ? (int) $last['discount'] : 0,
            'work_weeks' => $last ? (int) $last['work_weeks'] : 5,
        ], true);

        if ($last) {
            $itemModel = new QuoteItemModel();
            foreach ($itemModel->forQuote((int) $last['id']) as $it) {
                unset($it['id']);
                $it['quote_id'] = $quoteId;
                $itemModel->insert($it);
            }
        }

        return (int) $quoteId;
    }
}
