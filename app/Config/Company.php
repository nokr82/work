<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

// 업체 정보. .env에서 company.name = ..., company.phone = ... 처럼 덮어쓸 수 있다.
class Company extends BaseConfig
{
    // 화면 곳곳에 나오는 상호
    public string $name = '한빛인테리어';

    // 고객 견적 화면의 문의 전화번호. 비워 두면 전화 안내 문구를 숨긴다.
    public string $phone = '';

    // 개인정보 보관 기간 (문의 폼 동의 문구에 표시)
    public string $privacyRetention = '상담 종료 후 1년';
}
