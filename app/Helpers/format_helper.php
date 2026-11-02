<?php

// 화면 표시용 보조 함수 (만 원 표기, 단계 이름, 상대 시간 등)

if (! function_exists('man')) {
    // 4850000 -> "485만 원"
    function man($won): string
    {
        return number_format((int) round($won / 10000)) . '만 원';
    }
}

if (! function_exists('won')) {
    function won($n): string
    {
        return number_format((int) $n);
    }
}

if (! function_exists('stage_labels')) {
    function stage_labels(): array
    {
        return ['new' => '신규', 'consulting' => '상담 중', 'measure' => '실측', 'quoted' => '견적 발송', 'contract' => '계약'];
    }
}

if (! function_exists('stage_label')) {
    function stage_label(string $stage): string
    {
        return stage_labels()[$stage] ?? $stage;
    }
}

if (! function_exists('stage_tone')) {
    // 배지 색상: new=주황, measure/quoted=파랑, 나머지=회색
    function stage_tone(string $stage): string
    {
        if ($stage === 'new') {
            return 'warn';
        }

        return in_array($stage, ['measure', 'quoted', 'contract'], true) ? 'info' : 'neutral';
    }
}

if (! function_exists('time_ago')) {
    function time_ago(?string $datetime): string
    {
        if (! $datetime) {
            return '';
        }
        $diff = time() - strtotime($datetime);
        if ($diff < 3600) {
            return max(1, (int) floor($diff / 60)) . '분 전';
        }
        if ($diff < 86400) {
            return (int) floor($diff / 3600) . '시간 전';
        }

        return (int) floor($diff / 86400) . '일 전';
    }
}

if (! function_exists('short_date')) {
    // "10월 29일"
    function short_date(?string $datetime): string
    {
        return $datetime ? date('n월 j일', strtotime($datetime)) : '';
    }
}

if (! function_exists('when_label')) {
    // 오늘/내일이면 "내일 10:00", 아니면 "11월 5일 14:00"
    function when_label(?string $datetime): string
    {
        if (! $datetime) {
            return '';
        }
        $ts   = strtotime($datetime);
        $days = (int) floor((strtotime(date('Y-m-d', $ts)) - strtotime(date('Y-m-d'))) / 86400);
        $time = date('H:i', $ts);
        if ($days === 0) {
            return '오늘 ' . $time;
        }
        if ($days === 1) {
            return '내일 ' . $time;
        }

        return date('n월 j일', $ts) . ' ' . $time;
    }
}

if (! function_exists('mask_phone')) {
    // 010-1234-5678 -> 010-****-5678
    function mask_phone(string $phone): string
    {
        return preg_replace('/^(\d{2,3})-?\d{3,4}-?(\d{4})$/', '$1-****-$2', $phone) ?: $phone;
    }
}

if (! function_exists('quote_totals')) {
    // 품목 목록과 할인액으로 공급가·부가세·총액 계산
    function quote_totals(array $items, int $discount = 0): array
    {
        $subtotal = 0;
        foreach ($items as $it) {
            $subtotal += (float) $it['qty'] * ((int) $it['material'] + (int) $it['labor']);
        }
        $subtotal = (int) round($subtotal);
        $supply   = max(0, $subtotal - $discount);
        $vat      = (int) round($supply / 10);

        return ['subtotal' => $subtotal, 'supply' => $supply, 'vat' => $vat, 'total' => $supply + $vat];
    }
}

if (! function_exists('item_amount')) {
    function item_amount(array $it): int
    {
        return (int) round((float) $it['qty'] * ((int) $it['material'] + (int) $it['labor']));
    }
}

if (! function_exists('qty_label')) {
    // 24.0 -> "24"
    function qty_label($qty): string
    {
        return rtrim(rtrim(number_format((float) $qty, 1, '.', ''), '0'), '.');
    }
}
