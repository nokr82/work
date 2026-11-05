<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

// 숨은 허니팟 칸이 채워져 오면(자동 등록 봇) 조용히 403으로 끝낸다.
// CI 기본 honeypot 필터는 예외를 던져 500 에러 로그가 쌓이므로 검사는 이 필터로 한다.
class HoneypotCheck implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $name = config('Honeypot')->name;
        if ((string) $request->getPost($name) !== '') {
            return service('response')->setStatusCode(403)->setBody('잘못된 요청입니다.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
