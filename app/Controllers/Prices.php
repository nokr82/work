<?php

namespace App\Controllers;

use App\Models\PriceCategoryModel;
use App\Models\PriceItemModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Prices extends AppBase
{
    // 단가표 관리 (PC)
    public function index(): string
    {
        $cats = (new PriceCategoryModel())->withCounts();
        $selected = (int) $this->request->getGet('cat');

        $current = null;
        foreach ($cats as $c) {
            if ((int) $c['id'] === $selected) {
                $current = $c;
            }
        }
        $current = $current ?? ($cats[0] ?? null);

        $lastEdit = (new PriceItemModel())->selectMax('updated_at')->first();

        return view('prices/index', [
            'title'      => '단가표 관리 (PC)',
            'active'     => 'prices',
            'categories' => $cats,
            'current'    => $current,
            'items'      => $current ? (new PriceItemModel())->inCategory((int) $current['id']) : [],
            'editing'    => (int) $this->request->getGet('edit'),
            'itemTotal'  => array_sum(array_column($cats, 'item_count')),
            'lastEdit'   => $lastEdit['updated_at'] ?? null,
            'errors'     => session()->getFlashdata('errors') ?? [],
        ]);
    }

    // 품목 추가·수정 (id가 있으면 수정)
    public function saveItem()
    {
        $rules = [
            'category_id' => 'required|is_natural_no_zero',
            'name'        => 'required|max_length[100]',
            'unit'        => 'required|max_length[10]',
            'material'    => 'required|is_natural',
            'labor'       => 'required|is_natural',
        ];
        $catId = (int) $this->request->getPost('category_id');
        $labels = ['category_id' => '공종', 'name' => '품목 이름', 'unit' => '단위', 'material' => '재료비', 'labor' => '노무비'];
        if (! $this->check($rules, $labels)) {
            return redirect()->to(site_url('prices?cat=' . $catId))->with('errors', $this->validator->getErrors());
        }

        $data = [
            'category_id' => $catId,
            'name'        => trim((string) $this->request->getPost('name')),
            'unit'        => trim((string) $this->request->getPost('unit')),
            // 천 단위 쉼표가 들어와도 숫자로 받는다
            'material'    => (int) preg_replace('/\D/', '', (string) $this->request->getPost('material')),
            'labor'       => (int) preg_replace('/\D/', '', (string) $this->request->getPost('labor')),
        ];

        $items = new PriceItemModel();
        $id    = (int) $this->request->getPost('id');
        if ($id > 0) {
            $items->update($id, $data);
        } else {
            $items->insert($data + ['enabled' => 1]);
        }

        return redirect()->to(site_url('prices?cat=' . $catId));
    }

    // 사용 여부 토글
    public function toggle(string $id)
    {
        $items = new PriceItemModel();
        $item  = $items->find((int) $id);
        if (! $item) {
            throw PageNotFoundException::forPageNotFound();
        }
        $items->update($item['id'], ['enabled' => $item['enabled'] ? 0 : 1]);

        return redirect()->to(site_url('prices?cat=' . $item['category_id']));
    }

    public function addCategory()
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '' || mb_strlen($name) > 30) {
            return redirect()->back();
        }
        $cats = new PriceCategoryModel();
        $id   = $cats->insert(['name' => $name, 'sort_order' => $cats->countAllResults()], true);

        return redirect()->to(site_url('prices?cat=' . $id));
    }
}
