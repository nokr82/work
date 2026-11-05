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
            'material'    => 'required|is_natural|less_than[1000000000]',
            'labor'       => 'required|is_natural|less_than[1000000000]',
        ];
        $catId  = (int) $this->request->getPost('category_id');
        $labels = ['category_id' => '공종', 'name' => '품목 이름', 'unit' => '단위', 'material' => '재료비', 'labor' => '노무비'];

        // 천 단위 쉼표·공백을 먼저 지우고 검증한다 (수정 폼이 "12,000" 형태로 값을 채워 준다)
        $amount = static fn ($v): string => str_replace([',', ' '], '', trim((string) $v));
        $input  = [
            'category_id' => (string) $catId,
            'name'        => trim((string) $this->request->getPost('name')),
            'unit'        => trim((string) $this->request->getPost('unit')),
            'material'    => $amount($this->request->getPost('material')),
            'labor'       => $amount($this->request->getPost('labor')),
        ];
        if (! $this->check($rules, $labels, $input)) {
            return redirect()->to(site_url('prices?cat=' . $catId))->with('errors', $this->validator->getErrors());
        }

        $data = ['material' => (int) $input['material'], 'labor' => (int) $input['labor'], 'category_id' => $catId] + $input;

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
        // 견적 품목은 공종을 이름으로 구분하므로 같은 이름은 막는다
        $dup = $cats->where('name', $name)->first();
        if ($dup) {
            return redirect()->to(site_url('prices?cat=' . $dup['id']))->with('errors', ['name' => '이미 있는 공종이에요: ' . $name]);
        }
        $id = $cats->insert(['name' => $name, 'sort_order' => $cats->countAllResults()], true);

        return redirect()->to(site_url('prices?cat=' . $id));
    }
}
