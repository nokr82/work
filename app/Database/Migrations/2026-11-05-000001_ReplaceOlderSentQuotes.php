<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// 이미 발송된 견적 중 같은 문의에 더 높은 버전의 발송본이 있으면 'replaced'(대체됨)로 바꾼다.
// (새 버전을 보내도 옛 링크로 진행 요청이 되던 문제를 고치면서 기존 데이터도 맞춘다)
class ReplaceOlderSentQuotes extends Migration
{
    public function up()
    {
        $quotes = $this->db->prefixTable('quotes');
        $this->db->query(
            "UPDATE {$quotes} q
             JOIN (SELECT lead_id, MAX(version) AS v FROM {$quotes} WHERE status = 'sent' GROUP BY lead_id) m
               ON m.lead_id = q.lead_id
             SET q.status = 'replaced'
             WHERE q.status = 'sent' AND q.version < m.v"
        );
    }

    public function down()
    {
        $this->db->table('quotes')->where('status', 'replaced')->update(['status' => 'sent']);
    }
}
