<?php

namespace App\Controllers;

use App\Models\LeadLogModel;
use App\Models\LeadModel;
use App\Models\QuoteModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Leads extends AppBase
{
    // 전화 문의 등록 폼의 '문의 경로' 선택지
    public const SOURCES = ['전화', '카톡', '숨고', '블로그', '기타'];
    // 고객 문의 폼으로 들어온 문의의 경로
    private const FORM_SOURCE = '문의 폼';
    private const SPACES      = ['아파트', '빌라', '오피스텔', '상가'];

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
        return view('leads/new', ['title' => '전화 문의 등록', 'sources' => self::SOURCES, 'errors' => session()->getFlashdata('errors') ?? []]);
    }

    public function create()
    {
        $rules = [
            'name'  => 'required|max_length[50]',
            'phone' => 'required|regex_match[/^[0-9\-\s]{9,15}$/]',
            'source' => 'permit_empty|in_list[' . implode(',', self::SOURCES) . ']',
        ];
        if (! $this->check($rules, ['name' => '이름', 'phone' => '연락처', 'source' => '문의 경로'])) {
            return redirect()->to(site_url('leads/new'))->withInput()->with('errors', $this->validator->getErrors());
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

    // 문의 정보 수정 폼
    public function edit(string $id): string
    {
        $lead = $this->findLead($id);

        return view('leads/edit', [
            'title'   => '문의 정보 수정',
            'lead'    => $lead,
            'sources' => array_unique(array_merge(self::SOURCES, [self::FORM_SOURCE], [$lead['source']])),
            'spaces'  => self::SPACES,
            'errors'  => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function update(string $id)
    {
        $lead   = $this->findLead($id);
        $input  = [
            'name'         => trim((string) $this->request->getPost('name')),
            'phone'        => trim((string) $this->request->getPost('phone')),
            'source'       => trim((string) $this->request->getPost('source')),
            'address'      => trim((string) $this->request->getPost('address')),
            'space_type'   => trim((string) $this->request->getPost('space_type')),
            'size_py'      => trim((string) $this->request->getPost('size_py')),
            // 공사 범위는 쉼표로 구분해 입력 ("주방, 욕실")
            'scopes'       => implode(',', array_filter(array_map('trim', explode(',', (string) $this->request->getPost('scopes'))), 'strlen')),
            'budget'       => trim((string) $this->request->getPost('budget')),
            'desired_time' => trim((string) $this->request->getPost('desired_time')),
            'memo'         => trim((string) $this->request->getPost('memo')),
        ];
        // 원래 경로가 목록에 없던 값이어도(예전 데이터) 그대로 두는 것은 허용
        $sources = array_unique(array_merge(self::SOURCES, [self::FORM_SOURCE], [$lead['source']]));
        $rules   = [
            'name'         => 'required|max_length[50]',
            'phone'        => 'required|regex_match[/^[0-9\-\s]{9,15}$/]',
            'source'       => 'required|in_list[' . implode(',', $sources) . ']',
            'address'      => 'permit_empty|max_length[200]',
            'space_type'   => 'permit_empty|in_list[' . implode(',', self::SPACES) . ']',
            'size_py'      => 'permit_empty|is_natural_no_zero|less_than[1000]',
            'scopes'       => 'permit_empty|max_length[255]',
            'budget'       => 'permit_empty|max_length[50]',
            'desired_time' => 'permit_empty|max_length[100]',
        ];
        $labels = [
            'name' => '이름', 'phone' => '연락처', 'source' => '문의 경로', 'address' => '주소', 'space_type' => '공간 유형',
            'size_py' => '평수', 'scopes' => '공사 범위', 'budget' => '예산', 'desired_time' => '희망 시기',
        ];
        if (! $this->check($rules, $labels, $input)) {
            return redirect()->to(site_url('leads/' . $lead['id'] . '/edit'))->withInput()->with('errors', $this->validator->getErrors());
        }

        // 빈칸은 NULL로 저장 (평수 0, 빈 문자열이 화면에 찍히지 않게)
        $data = array_map(static fn ($v) => $v === '' ? null : $v, $input);
        $data['size_py'] = $data['size_py'] === null ? null : (int) $data['size_py'];
        (new LeadModel())->update($lead['id'], $data);

        // 무엇을 고쳤는지 기록
        $changed = array_keys(array_filter($data, static fn ($v, $k) => (string) $v !== (string) ($lead[$k] ?? ''), ARRAY_FILTER_USE_BOTH));
        if ($changed) {
            (new LeadLogModel())->add((int) $lead['id'], '정보 수정: ' . implode(', ', array_map(static fn ($k) => $labels[$k] ?? '메모', $changed)));
        }

        return redirect()->to(site_url('leads/' . $lead['id']));
    }

    // 문의 삭제: 견적·견적 품목·기록까지 함께 지운다 (스팸·중복·테스트 문의 정리용)
    public function delete(string $id)
    {
        $lead = $this->findLead($id);
        if ($this->request->getPost('confirm_name') !== $lead['name']) {
            return redirect()->to(site_url('leads/' . $lead['id'] . '/edit'))->with('errors', ['delete' => '삭제하려면 고객 이름을 정확히 입력해 주세요.']);
        }

        $db = db_connect();
        $db->transStart();
        $quoteIds = array_column((new QuoteModel())->select('id')->where('lead_id', $lead['id'])->findAll(), 'id');
        if ($quoteIds) {
            $db->table('quote_items')->whereIn('quote_id', $quoteIds)->delete();
            $db->table('quotes')->whereIn('id', $quoteIds)->delete();
        }
        $db->table('lead_logs')->where('lead_id', $lead['id'])->delete();
        $db->table('leads')->where('id', $lead['id'])->delete();
        $db->transComplete();
        if (! $db->transStatus()) {
            return redirect()->to(site_url('leads/' . $lead['id'] . '/edit'))->with('errors', ['delete' => '삭제 중 문제가 생겨 취소했어요. 다시 시도해 주세요.']);
        }

        return redirect()->to(site_url('leads'));
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

    // 실측 일정 저장 (비우면 일정 삭제). 신규·상담 단계였다면 '실측'으로 넘긴다.
    public function measure(string $id)
    {
        $lead = $this->findLead($id);
        $raw  = trim((string) $this->request->getPost('measure_at'));
        $ts   = $raw === '' ? null : strtotime($raw);
        if ($ts === false) {
            return redirect()->to(site_url('leads/' . $lead['id']));
        }

        $at = $ts ? date('Y-m-d H:i:00', $ts) : null;
        (new LeadModel())->update($lead['id'], ['measure_at' => $at]);
        (new LeadLogModel())->add((int) $lead['id'], $at ? '실측 일정: ' . date('n월 j일 H:i', $ts) : '실측 일정 취소');
        if ($at && in_array($lead['stage'], ['new', 'consulting'], true)) {
            $this->moveTo($lead, 'measure');
        }

        return redirect()->to(site_url('leads/' . $lead['id']));
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


        return view('leads/kanban', [
            'title'     => '문의 칸반 (PC)',
            'active'    => 'leads',
            'columns'   => $columns,
            'active_n'  => $leads->where('stage !=', 'contract')->countAllResults(),
            'contracts' => $leads->contractsThisMonth(),
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
            } elseif (! $quotes->sentCount((int) $l['id'])) {
                // 초안만 있고 아직 안 보냈어도 '미발송'이다. 초안이 있으면 그 초안으로 바로 간다.
                $top   = '실측 후 ' . (int) floor(($now - $at) / 86400) . '일 · 견적 미발송';
                $tone  = 'warn';
                $alert = true;
                $draft = $quotes->draftFor((int) $l['id']);
                if ($draft) {
                    $url = 'quotes/edit/' . $draft['id'];
                }
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
        (new LeadModel())->update($lead['id'], LeadModel::stageData($stage));
        (new LeadLogModel())->add((int) $lead['id'], '단계 변경: ' . stage_label($lead['stage']) . ' → ' . stage_label($stage));
    }
}
