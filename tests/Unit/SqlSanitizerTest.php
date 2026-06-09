<?php

declare(strict_types=1);

namespace Netfly\Observability\Tests\Unit;

use Netfly\Observability\Support\SqlSanitizer;
use PHPUnit\Framework\TestCase;

class SqlSanitizerTest extends TestCase
{
    public function testSanitizeSelect(): void
    {
        $sanitizer = new SqlSanitizer();
        $this->assertSame('SELECT', $sanitizer->sanitize('  select * from users where id = ?'));
    }

    public function testSanitizeInsert(): void
    {
        $sanitizer = new SqlSanitizer();
        $this->assertSame('INSERT', $sanitizer->sanitize('INSERT INTO users (name) VALUES (?)'));
    }

    public function testSanitizeUnknown(): void
    {
        $sanitizer = new SqlSanitizer();
        $this->assertSame('OTHER', $sanitizer->sanitize('LOCK TABLES users WRITE'));
    }
}
