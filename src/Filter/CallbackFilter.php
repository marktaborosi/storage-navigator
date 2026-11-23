<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Filter;

use Marktaborosi\StorageNavigator\Entry\Entry;

final readonly class CallbackFilter implements FilterInterface
{
    /** @var \Closure(Entry): bool */
    private \Closure $callback;

    /**
     * @param callable(Entry): bool $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback(...);
    }

    public function matches(Entry $entry): bool
    {
        return ($this->callback)($entry);
    }
}
