<?php

namespace App\Models;

use CodeIgniter\Model;

class PriceItemModel extends Model
{
    protected $table         = 'price_items';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['category_id', 'name', 'unit', 'material', 'labor', 'enabled'];

    public function inCategory(int $categoryId, bool $onlyEnabled = false): array
    {
        $builder = $this->where('category_id', $categoryId);
        if ($onlyEnabled) {
            $builder->where('enabled', 1);
        }

        return $builder->orderBy('id', 'ASC')->findAll();
    }
}
