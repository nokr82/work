<?php

namespace App\Controllers;

use App\Models\LeadLogModel;
use App\Models\LeadModel;
use App\Models\QuoteModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Leads extends AppBase
{
    // 문의 목록 (모바일): 단계 필터 + 검색
    public function index(): string
    {
        $leads = new LeadModel();
        $stage = $this->request->getGet('stage');
        $q     = trim((string) $this->request->getGet('q'));

        $counts = $leads->stageCounts();

        return view('leads/index', [
            'title'  => '문의 목록',
            'nav'    => 'leads',
            'stage'  => $stage,
            'q'      => $q,
            'total'  => array_sum($counts),
            'counts' => $counts,
            'leads'  => $leads->search($stage, $q),
        ]);
    }

    // 문의 상세
    public function show(string $id): string
    {
        $lead = $this->findLead($id);

        return view('leads/show', [
            'title'  => '문의 상세',
            'lead'   => $lead,
            'quotes' => (new QuoteModel())->forLead((int) $lead['id']),
            'logs'   => (new LeadLogModel())->forLead((int) $lead['id']),
        ]);
    }

    // 전화 문의 직접 등록 폼
    public function new(): string
    {
        return view('leads/new', ['title' => '전화 문의 등록', 'errors' => session()->getFlashdata('errors') ?? []]);
    }

    public function create()
    {
        $rules = [
            'name'  => 'required|max_length[50]',
            'phone' => 'required|regex_match[/^[0-9\-\s]{9,15}$/]',
        ];
        if (! $this->check($rules, ['name' => '이름', 'phone' => '연락처'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $leads = new LeadModel();
        $id    = $leads->insert([
            'name'    => trim((string) $this->request->getPost('name')),
            'phone'   => trim((string) $this->request->getPost('phone')),
            'source'  => (string) ($this->request->getPost('source') ?: '전화'),
            'stage'   => 'new',
            'address' => (string) $this->request->getPost('address'),
            'memo'    => (string) $this->request->getPost('memo'),
        ], true);
        (new LeadLogModel())->add((int) $id, '문의 직접 등록');

        return redirect()->to(site_url('leads/' . $id));
    }

    // 단계 변경 (폼)
    public function stage(string $id)
    {
        $lead  = $this->findLead($id);
        $stage = (string) $this->request->getPost('stage');
        $this->moveTo($lead, $stage);

        return redirect()->to(site_url('leads/' . $lead['id']));
    }

    // 칸반 드래그로 단계 변경 (JSON)
    public function move(string $id)
    {
        $lead  = $this->findLead($id);
        $stage = (string) $this->request->getPost('stage');
        if (! in_array($stage, LeadModel::STAGES, true)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false]);
        }
        $this->moveTo($lead, $stage);

        return $this->response->setJSON(['ok' => true]);
    }

    // 메모 추가
    public function memo(string $id)
    {
        $lead = $this->findLead($id);
        $text = trim((string) $this->request->getPost('memo'));
        if ($text !== '') {
            (new LeadLogModel())->add((int) $lead['id'], mb_substr($text, 0, 250));
        }

        return redirect()->to(site_url('leads/' . $lead['id']));
    }

    // 문의 칸반 (PC)
    public function kanban(): string
    {
        $leads  = new LeadModel();
        $quotes = new QuoteModel();
        $perCol = 3;
        $now    = time();

        $columns = [];
        foreach (LeadModel::STAGES as $stage) {
            $rows  = $leads->where('stage', $stage)->orderBy('created_at', 'DESC')->findAll();
            $cards = [];
            foreach (array_slice($rows, 0, $perCol) as $l) {
                $cards[] = $this->kanbanCard($l, $quotes, $now);
            }
            $columns[] = [
                'stage' => $stage,
                'name'  => stage_label($stage),
                'count' => count($rows),
                'more'  => max(0, count($rows) - $perCol),
                'cards' => $cards,
            ];
        }

        $monthStart = date('Y-m-01 00:00:00');

        return view('leads/kanban', [
            'title'     => '문의 칸반 (PC)',
            'active'    => 'leads',
            'columns'   => $columns,
            'active_n'  => $leads->where('stage !=', 'contract')->countAllResults(),
            'contracts' => $leads->where('stage', 'contract')->where('updated_at >=', $monthStart)->countAllResults(),
        ]);
    }

    // 칸반 카드 한 장의 문구와 강조 여부
    private function kanbanCard(array $l, QuoteModel $quotes, int $now): array
    {
        $top   = time_ago($l['created_at']) . ' · ' . $l['source'];
        $tone  = 'muted';
        $alert = false;
        $url   = 'leads/' . $l['id'];

        if ($l['stage'] === 'new' && $now - strtotime($l['created_at']) >= 3600) {
            $top = str_replace(' 전', '', time_ago($l['created_at'])) . '째 미응답';
            $tone = 'warn';
            $alert = true;
        } elseif ($l['stage'] === 'measure' && $l['measure_at']) {
            $at = strtotime($l['measure_at']);
            if ($at > $now) {
                $top  = when_label($l['measure_at']) . ' 실측';
                $tone = $at - $now <= 2 * 86400 ? 'info' : 'muted';
            } elseif (! $quotes->where('lead_id', $l['id'])->countAllResults()) {
                $top   = '실측 후 ' . (int) floor(($now - $at) / 86400) . '일 · 견적 미발송';
                $tone  = 'warn';
                $alert = true;
                $url   = 'quotes/new/' . $l['id'];
            }
        } elseif ($l['stage'] === 'quoted') {
            $q = $quotes->where('lead_id', $l['id'])->where('status', 'sent')->orderBy('version', 'DESC')->first();
            if ($q) {
                $totals = quote_totals((new \App\Models\QuoteItemModel())->forQuote((int) $q['id']), (int) $q['discount']);
                if (! empty($q['revision_note'])) {
                    $top  = '수정 요청 도착';
                    $tone = 'info';
                } elseif ((int) $q['view_count'] === 0) {
                    $top = '미열람 · ' . time_ago($q['sent_at']) . ' 발송';
                } else {
                    $days = (int) floor(($now - strtotime($q['sent_at'])) / 86400);
                    $top  = '열람 ' . $q['view_count'] . '회' . ($days >= 2 ? ' · ' . $days . '일째 응답 없음' : '');
                    if ($days >= 2) {
                        $tone  = 'warn';
                        $alert = true;
                    }
                }
                $desc = LeadModel::summary($l) . ' · v' . $q['version'] . ' · ' . man($totals['total']);
            }
        } elseif ($l['stage'] === 'contract') {
            $top  = '계약 완료';
            $tone = 'ok';
        }

        return [
            'id'    => $l['id'],
            'top'   => $top,
            'tone'  => $tone,
            'name'  => $l['name'],
            'desc'  => $desc ?? LeadModel::summary($l),
            'url'   => $url,
            'alert' => $alert,
        ];
    }

    private function findLead(string $id): array
    {
        $lead = (new LeadModel())->find((int) $id);
        if (! $lead) {
            throw PageNotFoundException::forPageNotFound('문의를 찾을 수 없습니다.');
        }

        return $lead;
    }

    // 단계를 바꾸고 기록을 남긴다
    private function moveTo(array $lead, string $stage): void
    {
        if (! in_array($stage, LeadModel::STAGES, true) || $stage === $lead['stage']) {
            return;
        }
        (new LeadModel())->update($lead['id'], ['stage' => $stage]);
        (new LeadLogModel())->add((int) $lead['id'], '단계 변경: ' . stage_label($lead['stage']) . ' → ' . stage_label($stage));
    }
}
