<?php

declare(strict_types=1);

namespace Netfly\Observability\Support;

use Hyperf\Contract\ConfigInterface;
use Netfly\Observability\Contract\FeatureSwitchInterface;

class FeatureSwitch implements FeatureSwitchInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->config->get('observability.enabled', true);
    }

    public function isModuleEnabled(string $module): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        return (bool) $this->config->get('observability.modules.' . $module, true);
    }
}
