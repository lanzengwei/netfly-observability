<?php

declare(strict_types=1);

namespace Netfly\Observability\Contract;

interface FeatureSwitchInterface
{
    public function isEnabled(): bool;

    public function isModuleEnabled(string $module): bool;
}
