<?php

declare(strict_types=1);

namespace Netfly\Observability\Tests\Feature;

use Netfly\Observability\ConfigProvider;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    public function testConfigProviderReturnsPublishDefinition(): void
    {
        if (! defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }

        $provider = (new ConfigProvider())();
        $this->assertArrayHasKey('publish', $provider);
        $this->assertArrayHasKey('dependencies', $provider);
        $this->assertNotEmpty($provider['publish']);
    }
}
