<?php

namespace App\Controllers;

use App\Models\LeadLogModel;
use App\Models\LeadModel;

// 고객이 로그인 없이 작성하는 4단계 문의 폼. 입력값은 세션에 쌓다가 마지막에 저장한다.
class Inquiry extends AppBase
{
    private const STEPS  = [1 => '연락처', 2 => '공사 범위', 3 => '예산과 시기', 4 => '확인'];
    private const SCOPES = ['전체 리모델링', '주방', '욕실', '도배', '바닥', '샷시', '조명·전기', '잘 모르겠어요'];

    public function form(): string
    {
        $step = (int) ($this->request->getGet('step') ?: 1);
        $step = max(1, min(4, $step));

        return view('inquiry/form', [
            'title'  => '고객 문의 폼',
            'white'  => true,
            'step'   => $step,
            'steps'  => self::STEPS,
            'data'   => session()->get('inquiry') ?? [],
            'errors' => session()->getFlashdata('errors') ?? [],
            'spaces' => ['아파트', '빌라', '오피스텔', '상가'],
            'scopes' => self::SCOPES,
            'budgets' => ['500만 원 미만', '500~1,000만 원', '1,000~3,000만 원', '3,000~5,000만 원', '5,000만 원 이상', '잘 모르겠어요'],
        ]);
    }

    // 각 단계의 입력을 검증하고 다음 단계로
    public function save(int $step)
    {
        $step = max(1, min(3, $step));

        $rules = [
            1 => ['name' => 'required|max_length[50]', 'phone' => 'required|regex_match[/^[0-9\-\s]{9,15}$/]'],
            2 => ['space' => 'required|in_list[아파트,빌라,오피스텔,상가]', 'size' => 'permit_empty|is_natural_no_zero|less_than[1000]'],
            3 => ['address' => 'permit_empty|max_length[200]', 'budget' => 'permit_empty|max_length[50]', 'desired_time' => 'permit_empty|max_length[100]'],
        ][$step];

        $labels = [
            'name' => '이름', 'phone' => '연락처', 'space' => '공간 유형', 'size' => '평수',
            'address' => '주소', 'budget' => '예산', 'desired_time' => '희망 시기',
        ];
        if (! $this->check($rules, $labels)) {
            return redirect()->to(site_url('inquiry?step=' . $step))->with('errors', $this->validator->getErrors());
        }

        $data = session()->get('inquiry') ?? [];
        foreach (array_keys($rules) as $field) {
            $data[$field] = $this->request->getPost($field);
        }
        if ($step === 2) {
            // 화면에 있는 선택지만 받는다 (임의 값이 저장되지 않게)
            $data['scopes'] = array_values(array_intersect(self::SCOPES, (array) $this->request->getPost('scopes')));
        }
        if ($step === 3) {
            $data['memo'] = $this->request->getPost('memo');
        }
        session()->set('inquiry', $data);

        return redirect()->to(site_url('inquiry?step=' . ($step + 1)));
    }

    // 마지막 단계: 문의로 저장
    public function submit()
    {
        $d = session()->get('inquiry') ?? [];
        if (empty($d['name']) || empty($d['phone'])) {
            return redirect()->to(site_url('inquiry?step=1'));
        }
        // 개인정보 수집·이용 동의는 필수 (브라우저 required를 우회한 요청도 막는다)
        if ($this->request->getPost('agree') !== '1') {
            return redirect()->to(site_url('inquiry?step=4'))->with('errors', ['agree' => '개인정보 수집·이용에 동의해 주셔야 접수할 수 있어요.']);
        }
        // 자동 등록 방지: 같은 IP에서 1시간에 5건까지
        $key = 'inquiry-' . md5((string) $this->request->getIPAddress());
        if (service('throttler')->check($key, 5, HOUR) === false) {
            return redirect()->to(site_url('inquiry?step=4'))->with('errors', ['rate' => '짧은 시간에 문의가 많이 접수됐어요. 급하시면 전화로 연락 주세요.']);
        }

        $leadId = (new LeadModel())->insert([
            'name'         => trim($d['name']),
            'phone'        => trim($d['phone']),
            'source'       => '문의 폼',
            'stage'        => 'new',
            'space_type'   => $d['space'] ?? null,
            'size_py'      => ! empty($d['size']) ? (int) $d['size'] : null,
            'scopes'       => implode(',', $d['scopes'] ?? []),
            'address'      => $d['address'] ?? null,
            'budget'       => $d['budget'] ?? null,
            'desired_time' => $d['desired_time'] ?? null,
            'memo'         => $d['memo'] ?? null,
            'privacy_agreed_at' => date('Y-m-d H:i:s'),
        ], true);
        (new LeadLogModel())->add((int) $leadId, '고객이 문의 폼으로 접수');

        session()->remove('inquiry');

        return redirect()->to(site_url('inquiry/done'));
    }

    public function done(): string
    {
        return view('inquiry/done', ['title' => '문의 접수 완료', 'white' => true]);
    }
}
