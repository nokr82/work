<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

// 사장님 화면 보호: 로그인하지 않았으면 로그인 화면으로 보낸다
class OwnerAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('owner')) {
            return null;
        }

        // JSON 요청(칸반 드래그)은 리다이렉트 대신 401
        if ($request->isAJAX() || stripos($request->getHeaderLine('Accept'), 'application/json') !== false) {
            return service('response')->setStatusCode(401)->setJSON(['ok' => false, 'login' => true]);
        }

        // 로그인 후 원래 보던 화면으로 돌아오도록 경로만 기억한다 (외부 주소로 튕기지 않게)
        $path = '/' . ltrim($request->getUri()->getPath(), '/');
        $query = $request->getUri()->getQuery();
        if (strtolower($request->getMethod()) === 'get') {
            session()->set('owner_next', $path . ($query !== '' ? '?' . $query : ''));
        }

        return redirect()->to(site_url('login'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
