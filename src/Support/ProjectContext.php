<?php

declare(strict_types=1);

namespace Netfly\Observability\Support;

use Hyperf\Contract\ConfigInterface;
use Netfly\Observability\Contract\ProjectContextInterface;

class ProjectContext implements ProjectContextInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function getProject(): string
    {
        return (string) $this->config->get('observability.project', 'default');
    }

    public function getService(): string
    {
        return (string) $this->config->get('observability.service', 'hyperf');
    }
}
