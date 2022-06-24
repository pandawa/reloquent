<?php

declare(strict_types=1);

namespace Pandawa\Reloquent;

use Cycle\ORM\EntityManager as BaseEntityManager;
use Pandawa\Reloquent\Entity\AggregateRoot;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class EntityManager extends BaseEntityManager
{
    /**
     * @var AggregateRoot[]
     */
    protected array $aggregateRoots = [];

    public function persistState(object $entity, bool $cascade = true): static
    {
        return tap(parent::persistState($entity, $cascade), fn() => $this->registerAggregateRoot($entity));
    }

    public function persist(object $entity, bool $cascade = true): static
    {
        return tap(parent::persist($entity, $cascade), fn() => $this->registerAggregateRoot($entity));
    }

    public function delete(object $entity, bool $cascade = true): static
    {
        return tap(parent::delete($entity, $cascade), fn() => $this->registerAggregateRoot($entity));
    }

    public function clean(bool $cleanHeap = false): static
    {
        return tap(parent::clean($cleanHeap), function () {
            foreach ($this->aggregateRoots as $aggregateRoot) {
                foreach ($aggregateRoot->pullEvents() as $event) {
                    event($event);
                }
            }
        });
    }

    protected function registerAggregateRoot(object $entity): void
    {
        if ($entity instanceof AggregateRoot) {
            $this->aggregateRoots[] = $entity;
        }
    }
}
