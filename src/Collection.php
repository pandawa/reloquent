<?php

declare(strict_types=1);

namespace Pandawa\Reloquent;

use InvalidArgumentException;
use Pandawa\Component\Ddd\Collection as BaseCollection;
use Pandawa\Reloquent\Contract\Mappable;
use Pandawa\Reloquent\Entity\Entity;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class Collection extends BaseCollection
{
    protected array $newItems = [];
    protected array $pendingRemovedItems = [];

    public function add($item)
    {
        $collection = $this->cloneCollection();

        if (!$item instanceof Mappable) {
            $item = $this->mapIfPossible($item);
        }

        $collection->newItems[] = $item;

        $collection->items[] = $item;

        return $collection;
    }

    public function remove(Entity $entity): static
    {
        $collection = $this->cloneCollection();

        $index = $collection->search(fn (Entity $item) => $item->getId() === $entity->getId());

        if ($index < 0) {
            throw new InvalidArgumentException(sprintf('Entity with id "%s" is not found.', $entity->getId()));
        }

        $collection->pendingRemovedItems[] = $collection->items[$index];
        $collection->pull($index);

        return $collection;
    }

    public function truncate(): static
    {
        $collection = $this->cloneCollection();

        foreach ($collection->items as $index => $item) {
            $collection->pendingRemovedItems[] = $collection->items[$index];
            $collection->pull($index);
        }

        return $collection;
    }

    public function getPendingRemovedItems(): array
    {
        return $this->pendingRemovedItems;
    }

    public function clearPendingRemovedItems(): void
    {
        $this->pendingRemovedItems = [];
    }

    public function getNewItems(): array
    {
        return $this->newItems;
    }

    public function clearNewItems(): void
    {
        $this->newItems = [];
    }

    protected function cloneCollection(): static
    {
        $collection = new static($this->items);
        $collection->newItems = $this->newItems;
        $collection->pendingRemovedItems = $this->pendingRemovedItems;

        return $collection;
    }

    private function mapIfPossible($item): mixed
    {
        if ($item instanceof Entity && !$item instanceof Mappable) {
            $mappedEntityClass = $this->getMappedEntityClass($item);

            return $mappedEntityClass::hydrateFromEntity($item);
        }

        return $item;
    }

    private function getMappedEntityClass($entity): string
    {
        $mapper = get_class($entity) . 'Map';

        return $mapper::getMappedClass();
    }

}
