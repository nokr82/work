<?php

namespace App\Controllers;

use CodeIgniter\Controller;

// 모든 화면 컨트롤러의 공통 부모 (필요한 헬퍼를 미리 불러온다)
abstract class AppBase extends Controller
{
    protected $helpers = ['url', 'form', 'format'];

    // 입력 검증. CI 기본 메시지가 영어라서 항목 이름($labels)을 넣은 한국어 메시지를 쓴다.
    protected function check(array $rules, array $labels): bool
    {
        $messages = [];
        foreach (array_keys($rules) as $field) {
            $label = $labels[$field] ?? $field;

            $messages[$field] = [
                'required'           => $label . '을(를) 입력해 주세요.',
                'max_length'         => $label . '이(가) 너무 깁니다.',
                'regex_match'        => $label . ' 형식을 확인해 주세요.',
                'in_list'            => $label . '을(를) 선택해 주세요.',
                'is_natural'         => $label . '은(는) 0 이상의 숫자로 입력해 주세요.',
                'is_natural_no_zero' => $label . '은(는) 1 이상의 숫자로 입력해 주세요.',
                'less_than'          => $label . ' 값이 너무 큽니다.',
            ];
        }

        return $this->validate($rules, $messages);
    }
}
