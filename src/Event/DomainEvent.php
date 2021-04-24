<?php

declare(strict_types=1);

namespace Pandawa\Reloquent\Event;

use DateTime;
use Illuminate\Support\Str;
use Pandawa\Reloquent\Contract\DomainEvent as DomainEventContract;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
abstract class DomainEvent implements DomainEventContract
{
    protected string $eventId;
    protected int $eventVersion;
    protected DateTime $occurredAt;

    public function __construct()
    {
        $this->eventId = Str::uuid()->toString();
        $this->eventVersion = 1;
        $this->occurredAt = new DateTime();
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function setEventId(string $eventId): static
    {
        $event = clone $this;
        $event->eventId = $eventId;

        return $event;
    }

    public function getEventVersion(): int
    {
        return $this->eventVersion;
    }

    public function setEventVersion(int $eventVersion): static
    {
        $event = clone $this;
        $event->eventVersion = $eventVersion;

        return $event;
    }

    public function getOccurredAt(): DateTime
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(DateTime $occurredAt): static
    {
        $event = clone $this;
        $event->occurredAt = $occurredAt;

        return $event;
    }
}
