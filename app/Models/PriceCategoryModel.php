<?php

namespace App\Models;

use CodeIgniter\Model;

class PriceCategoryModel extends Model
{
    protected $table         = 'price_categories';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'sort_order'];

    // 공종 목록 + 품목 수
    public function withCounts(): array
    {
        return $this->select('price_categories.*, COUNT(price_items.id) AS item_count')
            ->join('price_items', 'price_items.category_id = price_categories.id', 'left')
            ->groupBy('price_categories.id')
            ->orderBy('price_categories.sort_order', 'ASC')
            ->orderBy('price_categories.id', 'ASC')
            ->findAll();
    }
}
