<?php

declare(strict_types=1);

use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;

$container = new Container((new DefinitionSourceFactory())());

if (! $container instanceof Psr\Container\ContainerInterface) {
    throw new RuntimeException('Container not created.');
}

return $container;
