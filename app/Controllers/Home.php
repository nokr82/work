<?php

namespace App\Controllers;

use App\Models\LeadModel;
use App\Models\QuoteModel;

class Home extends AppBase
{
    // 사장님 홈: 이번 달 요약 + 오늘 연락할 고객
    public function index(): string
    {
        $leads  = new LeadModel();
        $quotes = new QuoteModel();

        $monthStart = date('Y-m-01 00:00:00');
        $inquiries  = $leads->where('created_at >=', $monthStart)->countAllResults();
        $sent       = $quotes->where('sent_at >=', $monthStart)->countAllResults();
        $contracts  = $leads->where('stage', 'contract')->where('updated_at >=', $monthStart)->countAllResults();
        $rate       = $inquiries > 0 ? (int) round($contracts / $inquiries * 100) : 0;

        $todos = $this->buildTodos($leads, $quotes);

        return view('home', [
            'title' => '사장님 홈',
            'nav'   => 'home',
            'todos' => $todos,
            'stats' => [
                ['label' => '이번 달 문의', 'value' => (string) $inquiries],
                ['label' => '견적 발송',   'value' => (string) $sent],
                ['label' => '계약',        'value' => (string) $contracts],
                ['label' => '계약률',      'value' => $rate . '%'],
            ],
        ]);
    }

    // 지금 연락하면 좋은 고객을 규칙으로 뽑는다
    private function buildTodos(LeadModel $leads, QuoteModel $quotes): array
    {
        $todos = [];
        $now   = time();

        foreach ($leads->orderBy('created_at', 'DESC')->findAll() as $l) {
            $item = ['lead' => $l, 'type' => '', 'label' => '', 'desc' => LeadModel::summary($l)];

            if ($l['stage'] === 'quoted') {
                $latest = $quotes->where('lead_id', $l['id'])->where('status', 'sent')->orderBy('version', 'DESC')->first();
                if ($latest && (int) $latest['view_count'] > 0 && $latest['sent_at'] && $now - strtotime($latest['sent_at']) >= 2 * 86400) {
                    $days          = (int) floor(($now - strtotime($latest['sent_at'])) / 86400);
                    $item['type']  = 'quote_stale';
                    $item['label'] = '견적 ' . $latest['view_count'] . '번 열람 · ' . $days . '일째 응답 없음';
                    $item['desc']  = LeadModel::summary($l) . ' · 견적 v' . $latest['version'];
                }
            } elseif ($l['stage'] === 'new' && $now - strtotime($l['created_at']) >= 3600) {
                $item['type']  = 'new_unanswered';
                $item['label'] = '신규 문의 · ' . str_replace(' 전', '', time_ago($l['created_at'])) . '째 미응답';
                $item['desc']  = LeadModel::summary($l) . ' · ' . $l['source'];
            } elseif ($l['stage'] === 'measure' && $l['measure_at']) {
                $at = strtotime($l['measure_at']);
                if ($at > $now && $at - $now <= 2 * 86400) {
                    $item['type']  = 'measure_soon';
                    $item['label'] = when_label($l['measure_at']) . ' 실측';
                } elseif ($at <= $now && ! $quotes->where('lead_id', $l['id'])->countAllResults()) {
                    $item['type']  = 'no_quote';
                    $item['label'] = '실측 후 ' . (int) floor(($now - $at) / 86400) . '일 · 견적 미발송';
                    $item['desc']  = LeadModel::summary($l) . ' · ' . $l['source'];
                }
            }

            if ($item['type'] !== '') {
                $todos[] = $item;
            }
        }

        // 견적 열람 후 무응답을 가장 위로
        usort($todos, static function ($a, $b) {
            return ($b['type'] === 'quote_stale') <=> ($a['type'] === 'quote_stale');
        });

        return $todos;
    }
}
