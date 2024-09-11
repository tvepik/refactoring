<?php
declare(strict_types=1);

namespace Refactoring\Providers\BINInfo;

interface BINInfoProvider
{
    public function fetch(string $bin): array;
}