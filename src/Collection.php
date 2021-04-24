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

    public function __construct($items = [])
    {
        foreach ($this->getArrayableItems($items) as $item) {
            $this->add($item);
        }
    }

    public function add($item)
    {
        if ($item instanceof Mappable) {
            $this->newItems[] = $item;

            return parent::add($item);
        }

        if ($item instanceof Entity) {
            $mappedEntityClass = $this->getMappedEntityClass($item);
            $item = $mappedEntityClass::hydrateFromEntity($item);

            $this->newItems[] = $item;
        }

        return parent::add($item);
    }

    public function remove(Entity $entity): static
    {
        $index = $this->search(fn (Entity $item) => $item->getId() === $entity->getId());

        if ($index < 0) {
            throw new InvalidArgumentException(sprintf('Entity with id "%s" is not found.', $entity->getId()));
        }

        $this->pendingRemovedItems[] = $this->items[$index];
        $this->pull($index);

        return $this;
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

    private function getMappedEntityClass($entity): string
    {
        $mapper = get_class($entity) . 'Map';

        return $mapper::getMappedClass();
    }

}
