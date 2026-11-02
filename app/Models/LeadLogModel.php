<?php

namespace App\Models;

use CodeIgniter\Model;

class LeadLogModel extends Model
{
    protected $table         = 'lead_logs';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    protected $allowedFields = ['lead_id', 'message'];

    // 문의 기록 한 줄 남기기
    public function add(int $leadId, string $message): void
    {
        $this->insert(['lead_id' => $leadId, 'message' => $message]);
    }

    public function forLead(int $leadId): array
    {
        return $this->where('lead_id', $leadId)->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->findAll();
    }
}
