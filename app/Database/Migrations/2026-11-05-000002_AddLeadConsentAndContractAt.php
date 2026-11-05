<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// leads에 두 시각을 추가한다.
// - privacy_agreed_at: 고객이 문의 폼에서 개인정보 수집·이용에 동의한 시각 (동의 증빙)
// - contracted_at: 계약 단계가 된 시각. "이번 달 계약"을 updated_at으로 세면
//   지난달 계약 건도 이번 달에 메모·일정만 고쳐도 다시 집계되는 문제가 있어 따로 둔다.
class AddLeadConsentAndContractAt extends Migration
{
    public function up()
    {
        $this->forge->addColumn('leads', [
            'privacy_agreed_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'measure_at'],
            'contracted_at'     => ['type' => 'DATETIME', 'null' => true, 'after' => 'privacy_agreed_at'],
        ]);

        // 이미 계약된 건: 기록에서 계약으로 바뀐 마지막 시각을 쓰고, 없으면 updated_at으로 채운다
        $leads = $this->db->prefixTable('leads');
        $logs  = $this->db->prefixTable('lead_logs');
        $this->db->query(
            "UPDATE {$leads} l
             LEFT JOIN (SELECT lead_id, MAX(created_at) AS at FROM {$logs}
                        WHERE message LIKE '%→ 계약%' GROUP BY lead_id) g ON g.lead_id = l.id
             SET l.contracted_at = COALESCE(g.at, l.updated_at)
             WHERE l.stage = 'contract'"
        );
    }

    public function down()
    {
        $this->forge->dropColumn('leads', ['privacy_agreed_at', 'contracted_at']);
    }
}
