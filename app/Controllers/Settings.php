<?php

namespace App\Controllers;

// 설정: 업체 정보 확인과 PC 화면·로그아웃 바로가기.
// 업체 정보는 서버 .env에서 바꾼다 (화면에서 바꾸면 DB 테이블이 하나 더 필요해서 지금은 읽기 전용).
class Settings extends AppBase
{
    public function index(): string
    {
        return view('settings', [
            'title' => '설정',
            'nav'   => 'settings',
        ]);
    }
}
