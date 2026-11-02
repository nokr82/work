<?php

namespace App\Models;

use CodeIgniter\Model;

class QuoteItemModel extends Model
{
    protected $table         = 'quote_items';
    protected $returnType    = 'array';
    protected $allowedFields = ['quote_id', 'category', 'name', 'unit', 'qty', 'material', 'labor', 'sort_order'];

    public function forQuote(int $quoteId): array
    {
        return $this->where('quote_id', $quoteId)->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->findAll();
    }
}
