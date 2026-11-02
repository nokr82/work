<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTables extends Migration
{
    private function id(): array
    {
        return ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true];
    }

    private function fk(): array
    {
        return ['type' => 'INT', 'constraint' => 10, 'unsigned' => true];
    }

    private function str(int $len, bool $null = false, $default = null): array
    {
        $f = ['type' => 'VARCHAR', 'constraint' => $len, 'null' => $null];
        if ($default !== null) {
            $f['default'] = $default;
        }

        return $f;
    }

    private function num(int $default = 0): array
    {
        return ['type' => 'INT', 'constraint' => 11, 'default' => $default];
    }

    private function dt(): array
    {
        return ['type' => 'DATETIME', 'null' => true];
    }

    public function up()
    {
        // 문의
        $this->forge->addField([
            'id'           => $this->id(),
            'name'         => $this->str(50),
            'phone'        => $this->str(30),
            'source'       => $this->str(20, false, '문의 폼'),
            'stage'        => $this->str(20, false, 'new'),   // new | consulting | measure | quoted | contract
            'address'      => $this->str(200, true),
            'space_type'   => $this->str(20, true),
            'size_py'      => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'scopes'       => $this->str(255, true),          // 쉼표로 구분
            'budget'       => $this->str(50, true),
            'desired_time' => $this->str(100, true),
            'memo'         => ['type' => 'TEXT', 'null' => true],
            'photo_count'  => $this->num(),
            'measure_at'   => $this->dt(),
            'created_at'   => $this->dt(),
            'updated_at'   => $this->dt(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('stage');
        $this->forge->createTable('leads', true);

        // 문의 기록
        $this->forge->addField([
            'id'         => $this->id(),
            'lead_id'    => $this->fk(),
            'message'    => $this->str(255),
            'created_at' => $this->dt(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('lead_id');
        $this->forge->createTable('lead_logs', true);

        // 단가표: 공종
        $this->forge->addField([
            'id'         => $this->id(),
            'name'       => $this->str(30),
            'sort_order' => $this->num(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('price_categories', true);

        // 단가표: 품목
        $this->forge->addField([
            'id'          => $this->id(),
            'category_id' => $this->fk(),
            'name'        => $this->str(100),
            'unit'        => $this->str(10),
            'material'    => $this->num(),
            'labor'       => $this->num(),
            'enabled'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => $this->dt(),
            'updated_at'  => $this->dt(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('category_id');
        $this->forge->createTable('price_items', true);

        // 견적 (버전별)
        $this->forge->addField([
            'id'             => $this->id(),
            'lead_id'        => $this->fk(),
            'version'        => $this->num(1),
            'token'          => $this->str(40),
            'status'         => $this->str(10, false, 'draft'), // draft | sent
            'discount'       => $this->num(),
            'work_weeks'     => $this->num(5),
            'valid_until'    => ['type' => 'DATE', 'null' => true],
            'sent_at'        => $this->dt(),
            'view_count'     => $this->num(),
            'last_viewed_at' => $this->dt(),
            'revision_note'  => ['type' => 'TEXT', 'null' => true],
            'created_at'     => $this->dt(),
            'updated_at'     => $this->dt(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token');
        $this->forge->addKey('lead_id');
        $this->forge->createTable('quotes', true);

        // 견적 품목 (단가표 값을 복사해 저장 → 단가가 바뀌어도 보낸 견적은 그대로)
        $this->forge->addField([
            'id'         => $this->id(),
            'quote_id'   => $this->fk(),
            'category'   => $this->str(30),
            'name'       => $this->str(100),
            'unit'       => $this->str(10),
            'qty'        => ['type' => 'DECIMAL', 'constraint' => '10,1', 'default' => 1],
            'material'   => $this->num(),
            'labor'      => $this->num(),
            'sort_order' => $this->num(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('quote_id');
        $this->forge->createTable('quote_items', true);
    }

    public function down()
    {
        foreach (['quote_items', 'quotes', 'price_items', 'price_categories', 'lead_logs', 'leads'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
