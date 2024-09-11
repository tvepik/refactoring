<?php
declare(strict_types=1);

namespace Refactoring\Providers\BINInfo;

use Refactoring\Providers\ExternalProvider;

class ExternalBINInfoProvider extends ExternalProvider implements BINInfoProvider
{
    public function fetch(string $bin): array
    {
        return $this->fetchData($this->getOption('url') . $bin);
    }
}