<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// 시안에 나온 예시 데이터를 넣는다. 실행: php spark db:seed DemoSeeder
class DemoSeeder extends Seeder
{
    private function ago(string $modify): string
    {
        return date('Y-m-d H:i:s', strtotime($modify));
    }

    private function lead(array $row): int
    {
        $created = $row['created_at'];
        $this->db->table('leads')->insert($row + [
            'updated_at' => $created,
        ]);

        return (int) $this->db->insertID();
    }

    private function log(int $leadId, string $message, string $at): void
    {
        $this->db->table('lead_logs')->insert(['lead_id' => $leadId, 'message' => $message, 'created_at' => $at]);
    }

    // 견적 만들기: $items = [[공종, 품목, 단위, 수량, 재료, 노무], ...]
    private function quote(int $leadId, int $version, string $status, array $items, array $extra = []): int
    {
        $this->db->table('quotes')->insert($extra + [
            'lead_id'    => $leadId,
            'version'    => $version,
            'token'      => bin2hex(random_bytes(10)),
            'status'     => $status,
            'created_at' => $this->ago('-5 days'),
            'updated_at' => $this->ago('-5 days'),
        ]);
        $id = (int) $this->db->insertID();
        foreach ($items as $i => $it) {
            $this->db->table('quote_items')->insert([
                'quote_id' => $id, 'category' => $it[0], 'name' => $it[1], 'unit' => $it[2],
                'qty' => $it[3], 'material' => $it[4], 'labor' => $it[5], 'sort_order' => $i,
            ]);
        }

        return $id;
    }

    public function run()
    {
        // ---- 단가표 ----
        $catalog = [
            '철거'      => [['기존 마감재 철거', '㎡', 8000, 12000], ['폐기물 처리', '식', 300000, 200000]],
            '목공'      => [['천장 평탄화·몰딩', '㎡', 15000, 20000], ['붙박이장 제작', 'm', 180000, 120000]],
            '욕실'      => [
                ['벽·바닥 타일 (국산)', '㎡', 32000, 28000],
                ['벽·바닥 타일 (수입)', '㎡', 55000, 28000],
                ['방수 공사', '㎡', 12000, 18000],
                ['욕조 철거 후 샤워부스', '식', 1100000, 750000],
                ['양변기·세면대·수전', '세트', 820000, 330000],
                ['천장재 (SMC)', '곳', 180000, 85000],
                ['욕실장 (상부)', '개', 150000, 40000, 0],
            ],
            '주방'      => [['싱크대 교체', 'm', 450000, 150000], ['주방 상판·타일', '식', 900000, 400000]],
            '도배'      => [['실크 도배', '㎡', 6000, 9000], ['합지 도배', '㎡', 3500, 7500]],
            '바닥'      => [['강마루 시공', '㎡', 42000, 18000], ['장판 시공', '㎡', 14000, 8000]],
            '전기·조명' => [['조명 교체', '개', 35000, 25000], ['콘센트·스위치 교체', '개', 8000, 12000]],
            '샷시'      => [['시스템 샷시 교체', '㎡', 180000, 60000]],
            '기타'      => [['현장 정리·청소', '식', 100000, 200000]],
        ];
        $sort = 0;
        foreach ($catalog as $catName => $items) {
            $this->db->table('price_categories')->insert(['name' => $catName, 'sort_order' => $sort++]);
            $catId = (int) $this->db->insertID();
            foreach ($items as $it) {
                $this->db->table('price_items')->insert([
                    'category_id' => $catId, 'name' => $it[0], 'unit' => $it[1],
                    'material' => $it[2], 'labor' => $it[3], 'enabled' => $it[4] ?? 1,
                    'created_at' => $this->ago('now'), 'updated_at' => $this->ago('now'),
                ]);
            }
        }

        // ---- 문의 ----
        $new = function (string $name, string $source, string $ago, array $more) {
            $at = $this->ago($ago);

            return $this->lead($more + [
                'name' => $name, 'phone' => '010-' . random_int(1000, 9999) . '-' . random_int(1000, 9999),
                'source' => $source, 'stage' => 'new', 'created_at' => $at,
            ]);
        };

        $new('박준호', '숨고', '-2 hours', ['space_type' => '아파트', 'address' => '경기 고양시', 'scopes' => '욕실', 'budget' => '600~800만 원', 'memo' => '욕실 1곳 리모델링']);
        $new('정하늘', '문의 폼', '-5 hours', ['space_type' => '아파트', 'size_py' => 34, 'address' => '서울 송파구', 'scopes' => '전체 리모델링', 'photo_count' => 6]);

        // 김민지: 견적 v1(수정 요청) → v2(3번 열람)
        $minji = $new('김민지', '전화', '-12 days', [
            'phone' => '010-5555-1234', 'stage' => 'quoted', 'space_type' => '아파트', 'size_py' => 32,
            'address' => '서울 성동구 행당동 ○○아파트', 'scopes' => '주방,욕실,도배,바닥,조명·전기',
            'budget' => '4,500~5,000만 원', 'desired_time' => '12월 중순 착공', 'photo_count' => 8,
        ]);
        $this->quote($minji, 1, 'sent', [['전체', '전체 공사 일괄', '식', 1, 30000000, 16545000]], [
            'sent_at' => $this->ago('-9 days'), 'valid_until' => date('Y-m-d', strtotime('-1 days')),
            'view_count' => 2, 'last_viewed_at' => $this->ago('-8 days'),
            'revision_note' => '욕실 타일 등급 낮춰 주세요',
        ]);
        $this->quote($minji, 2, 'sent', [
            ['주방', '주방 공사 일괄', '식', 1, 700000 * 10, 4800000],
            ['목공', '목공 공사 일괄', '식', 1, 5200000, 4000000],
            ['욕실', '벽·바닥 타일 (국산)', '㎡', 26, 40000, 30000],
            ['욕실', '욕조 철거 후 샤워부스', '식', 1, 1100000, 750000],
            ['욕실', '양변기·세면대·수전', '세트', 2, 820000, 330000],
            ['욕실', '천장재 (SMC)', '곳', 2, 180000, 85000],
            ['바닥', '바닥 공사 일괄', '식', 1, 3200000, 2000000],
            ['전기·조명', '전기·조명 일괄', '식', 1, 2400000, 1690000],
            ['기타', '철거·도배·기타 일괄', '식', 1, 4300000, 3000000],
        ], [
            'sent_at' => $this->ago('-3 days'), 'valid_until' => date('Y-m-d', strtotime('+10 days')),
            'view_count' => 3, 'last_viewed_at' => $this->ago('-1 day'),
        ]);
        $this->log($minji, '문의 접수 (전화)', $this->ago('-12 days'));
        $this->log($minji, '통화: 욕실 타일 국산으로 변경 원함', $this->ago('-5 days'));
        $this->log($minji, '견적 v2 발송', $this->ago('-3 days'));
        $this->log($minji, '고객이 견적 v2를 열람 (3번째)', $this->ago('-1 day'));

        $new('이수연', '카톡', '-1 day', [
            'stage' => 'measure', 'space_type' => '아파트', 'size_py' => 24, 'address' => '서울 마포구',
            'scopes' => '주방,도배', 'measure_at' => date('Y-m-d 10:00:00', strtotime('+1 day')),
        ]);
        $new('윤서진', '블로그', '-2 days', ['stage' => 'consulting', 'space_type' => '오피스텔', 'size_py' => 18, 'address' => '서울 강서구', 'scopes' => '도배,바닥']);
        $new('한지우', '전화', '-3 days', ['stage' => 'consulting', 'space_type' => '빌라', 'size_py' => 28, 'scopes' => '주방,샷시']);
        $new('오세훈', '카톡', '-4 days', ['stage' => 'consulting', 'space_type' => '상가', 'scopes' => '전체 리모델링']);
        $new('최동현', '블로그', '-6 days', [
            'stage' => 'measure', 'space_type' => '상가', 'address' => '상가 1층 카페',
            'measure_at' => date('Y-m-d 14:00:00', strtotime('-3 days')),
        ]);
        $new('강민서', '문의 폼', '-2 days', [
            'stage' => 'measure', 'space_type' => '아파트', 'size_py' => 42, 'address' => '분당구', 'scopes' => '전체 리모델링',
            'measure_at' => date('Y-m-d 14:00:00', strtotime('+3 days')),
        ]);
        $yuna = $new('서유나', '문의 폼', '-4 days', ['stage' => 'quoted', 'space_type' => '아파트', 'address' => '서울', 'scopes' => '욕실']);
        $this->quote($yuna, 1, 'sent', [['욕실', '욕실 공사 일괄', '식', 1, 6000000, 4200000]], [
            'sent_at' => $this->ago('-1 day'), 'valid_until' => date('Y-m-d', strtotime('+13 days')),
        ]);
        $doyun = $new('임도윤', '전화', '-8 days', ['stage' => 'quoted', 'space_type' => '아파트', 'size_py' => 25, 'scopes' => '전체 리모델링']);
        $this->quote($doyun, 1, 'sent', [['전체', '전체 공사 일괄', '식', 1, 18000000, 12727273]], [
            'sent_at' => $this->ago('-4 days'), 'valid_until' => date('Y-m-d', strtotime('+10 days')),
            'view_count' => 1, 'last_viewed_at' => $this->ago('-3 days'), 'revision_note' => '주방 상판 색상을 바꾸고 싶어요',
        ]);
        $new('조은비', '문의 폼', '-20 days', ['stage' => 'contract', 'space_type' => '아파트', 'size_py' => 30, 'scopes' => '전체 리모델링']);
        $new('배성민', '전화', '-30 days', ['stage' => 'contract', 'space_type' => '아파트', 'scopes' => '주방']);
    }
}
