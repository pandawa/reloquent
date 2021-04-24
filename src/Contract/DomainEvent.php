<?php

declare(strict_types=1);

namespace Pandawa\Reloquent\Contract;

use DateTime;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface DomainEvent
{
    public function getEventId(): string;

    public function setEventId(string $eventId): static;

    public function getEventVersion(): int;

    public function setEventVersion(int $version): static;

    public function getOccurredAt(): DateTime;

    public function setOccurredAt(DateTime $occurredAt): static;
}
