<?php

declare(strict_types=1);

namespace Netfly\Observability\Contract;

interface ProjectContextInterface
{
    public function getProject(): string;

    public function getService(): string;
}
