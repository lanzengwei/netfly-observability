<?php

declare(strict_types=1);

namespace Netfly\Observability\Support;

class SqlSanitizer
{
    public function sanitize(string $sql): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $sql) ?? $sql);

        if (preg_match('/^(SELECT|INSERT|UPDATE|DELETE|REPLACE|CALL|SHOW|DESCRIBE|EXPLAIN|CREATE|ALTER|DROP|TRUNCATE|BEGIN|COMMIT|ROLLBACK)\b/i', $normalized, $matches)) {
            return strtoupper($matches[1]);
        }

        return 'OTHER';
    }
}
