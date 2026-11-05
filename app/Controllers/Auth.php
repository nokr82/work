<?php

namespace App\Controllers;

// 사장님 로그인. 비밀번호는 .env의 OWNER_PASSWORD 하나로 관리한다.
class Auth extends AppBase
{
    public function form()
    {
        if ($this->isOwner()) {
            return redirect()->to(site_url('/'));
        }

        return view('auth/login', [
            'title'      => '사장님 로그인',
            'white'      => true,
            'error'      => session()->getFlashdata('error'),
            'configured' => $this->password() !== '',
        ]);
    }

    public function login()
    {
        $password = $this->password();
        if ($password === '') {
            return redirect()->to(site_url('login'));
        }

        // 비밀번호 대입 방지: IP당 1분에 5번까지
        $key = 'login-' . md5((string) $this->request->getIPAddress());
        if (service('throttler')->check($key, 5, MINUTE) === false) {
            return redirect()->to(site_url('login'))->with('error', '시도가 너무 많아요. 1분 뒤에 다시 해 주세요.');
        }

        $input = (string) $this->request->getPost('password');
        if (! hash_equals($password, $input)) {
            return redirect()->to(site_url('login'))->with('error', '비밀번호가 맞지 않아요.');
        }

        $next = (string) session()->get('owner_next');
        session()->regenerate(true);
        session()->set('owner', true);
        session()->remove('owner_next');

        // 내부 경로만 허용 ("//다른사이트" 같은 값 차단)
        $next = (strpos($next, '/') === 0 && strpos($next, '//') !== 0) ? $next : '/';

        return redirect()->to(site_url(ltrim($next, '/')));
    }

    public function logout()
    {
        session()->remove('owner');
        session()->regenerate(true);

        return redirect()->to(site_url('login'));
    }

    private function password(): string
    {
        return (string) env('OWNER_PASSWORD', '');
    }
}
