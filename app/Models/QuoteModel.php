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

    // 새 초안 만들기: 가장 최근 버전의 품목을 복사한다
    public function createDraft(int $leadId): int
    {
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
