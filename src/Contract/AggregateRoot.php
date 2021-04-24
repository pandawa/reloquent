<?php

declare(strict_types=1);

namespace Pandawa\Reloquent\Contract;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface AggregateRoot
{
    public function getVersion(): int;

    public function loadFromEventStream(array $eventStream): void;

    public function pullEvents(): array;
}
