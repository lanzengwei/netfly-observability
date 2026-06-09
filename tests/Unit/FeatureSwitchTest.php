<?php

declare(strict_types=1);

namespace Netfly\Observability\Tests\Unit;

use Hyperf\Contract\ConfigInterface;
use Netfly\Observability\Support\FeatureSwitch;
use PHPUnit\Framework\TestCase;

class FeatureSwitchTest extends TestCase
{
    public function testGlobalEnabled(): void
    {
        $switch = new FeatureSwitch($this->makeConfig(['enabled' => true]));
        $this->assertTrue($switch->isEnabled());
        $this->assertTrue($switch->isModuleEnabled('http'));
    }

    public function testGlobalDisabled(): void
    {
        $switch = new FeatureSwitch($this->makeConfig(['enabled' => false]));
        $this->assertFalse($switch->isEnabled());
        $this->assertFalse($switch->isModuleEnabled('http'));
    }

    public function testModuleDisabled(): void
    {
        $switch = new FeatureSwitch($this->makeConfig([
            'enabled' => true,
            'modules' => ['http' => false, 'mysql' => true],
        ]));
        $this->assertFalse($switch->isModuleEnabled('http'));
        $this->assertTrue($switch->isModuleEnabled('mysql'));
    }

    /**
     * @param array<string, mixed> $values
     */
    private function makeConfig(array $values): ConfigInterface
    {
        return new class ($values) implements ConfigInterface {
            /** @var array<string, mixed> */
            private $values;

            /** @param array<string, mixed> $values */
            public function __construct(array $values)
            {
                $this->values = $values;
            }

            public function get(string $key, mixed $default = null): mixed
            {
                if ($key === 'observability.enabled') {
                    return $this->values['enabled'] ?? $default;
                }
                if ($key === 'observability.modules.http') {
                    return $this->values['modules']['http'] ?? $default;
                }
                if ($key === 'observability.modules.mysql') {
                    return $this->values['modules']['mysql'] ?? $default;
                }

                return $default;
            }

            public function has(string $key): bool
            {
                return true;
            }

            public function set(string $key, $value): void
            {
            }
        };
    }
}
