<?php

declare(strict_types=1);

namespace Pandawa\Reloquent\Entity;

use Pandawa\Reloquent\Contract\DomainEvent;
use Pandawa\Reloquent\Contract\AggregateRoot as AggregateRootContract;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
abstract class AggregateRoot extends Entity implements AggregateRootContract
{
    protected int $version = 0;

    /**
     * @var DomainEvent[]
     */
    protected array $domainEvents = [];

    public function getVersion(): int
    {
        return $this->version;
    }

    public function loadFromEventStream(array $eventStream): void
    {
        foreach ($eventStream as $event) {
            $this->applyEvent($event);
        }
    }

    public function pullEvents(): array
    {
        return tap($this->domainEvents, fn() => $this->domainEvents = []);
    }

    protected function addEvent(DomainEvent $event): static
    {
        $this->domainEvents[] = $event = $event->setEventVersion(++$this->version);

        $this->applyEvent($event);

        return $this;
    }

    protected function applyEvent(DomainEvent $event): void
    {
        $className = class_basename($event);
        $methodName = 'apply' . ucfirst($className);

        if (method_exists($this, $methodName)) {
            $this->{$methodName}($event);
        }
    }
}
