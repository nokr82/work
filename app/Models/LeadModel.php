<?php

namespace App\Models;

use CodeIgniter\Model;

class LeadModel extends Model
{
    protected $table         = 'leads';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'name', 'phone', 'source', 'stage', 'address', 'space_type', 'size_py', 'scopes',
        'budget', 'desired_time', 'memo', 'photo_count', 'measure_at',
        'privacy_agreed_at', 'contracted_at',
    ];

    // 단계를 바꿀 때 함께 저장할 값. 계약이 되면 계약 시각을 남기고, 계약에서 빠지면 지운다.
    public static function stageData(string $stage): array
    {
        return ['stage' => $stage, 'contracted_at' => $stage === 'contract' ? date('Y-m-d H:i:s') : null];
    }

    // 이번 달 계약 건수 (계약 시각 기준)
    public function contractsThisMonth(): int
    {
        return $this->where('stage', 'contract')->where('contracted_at >=', date('Y-m-01 00:00:00'))->countAllResults();
    }

    public const STAGES = ['new', 'consulting', 'measure', 'quoted', 'contract'];

    // 단계·검색어로 목록 조회 (최신순)
    public function search(?string $stage, ?string $q): array
    {
        $builder = $this->orderBy('created_at', 'DESC');
        if ($stage && in_array($stage, self::STAGES, true)) {
            $builder->where('stage', $stage);
        }
        if ($q !== null && $q !== '') {
            $builder->groupStart()
                ->like('name', $q)
                ->orLike('phone', $q)
                ->orLike('address', $q)
                ->groupEnd();
        }

        return $builder->findAll();
    }

    // 단계별 건수 ['new' => 2, ...]
    public function stageCounts(): array
    {
        $counts = array_fill_keys(self::STAGES, 0);
        $rows   = $this->select('stage, COUNT(*) AS cnt')->groupBy('stage')->findAll();
        foreach ($rows as $r) {
            $counts[$r['stage']] = (int) $r['cnt'];
        }

        return $counts;
    }

    // 목록에 보여줄 한 줄 요약
    public static function summary(array $lead): string
    {
        $parts = [];
        if (! empty($lead['size_py'])) {
            $parts[] = $lead['size_py'] . '평' . (! empty($lead['space_type']) ? ' ' . $lead['space_type'] : '');
        } elseif (! empty($lead['space_type'])) {
            $parts[] = $lead['space_type'];
        }
        if (! empty($lead['address'])) {
            $parts[] = $lead['address'];
        }
        if (! empty($lead['scopes'])) {
            $parts[] = str_replace(',', ', ', $lead['scopes']);
        }
        if (! empty($lead['budget'])) {
            $parts[] = '예산 ' . $lead['budget'];
        }

        return implode(' · ', $parts);
    }
}
