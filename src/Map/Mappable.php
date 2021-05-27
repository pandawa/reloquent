<?php

declare(strict_types=1);

namespace Pandawa\Reloquent\Map;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection as LaravelCollection;
use InvalidArgumentException;
use Pandawa\Component\Ddd\Relationship\BelongsTo;
use Pandawa\Component\Ddd\Relationship\HasMany;
use Pandawa\Reloquent\Collection;
use Pandawa\Reloquent\Contract\Mappable as MappableContract;
use Pandawa\Reloquent\Entity\Entity;
use ReflectionClass;
use ReflectionObject;
use ReflectionProperty;
use RuntimeException;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 *
 * @mixin EntityMap
 */
trait Mappable
{
    protected ?EntityMap $entityMap = null;
    protected array $relations = [];
    protected array $skipAttributes = [
        'domainEvents',
    ];

    public static function hydrateFromEntity($entity)
    {
        if ($entity instanceof MappableContract) {
            return $entity;
        }

        $entityClass = static::class;
        $newEntity = new $entityClass;

        foreach ($newEntity->getEntityData($entity) as $key => $value) {
            if (method_exists($newEntity, $method = 'set'.ucfirst($key))) {
                $newEntity->{$method}($value);

                continue;
            }

            $newEntity->{$key} = $value;
        }

        return $newEntity;
    }

    public function getEntityData($entity): array
    {
        $reflection = new ReflectionObject($entity);
        $data = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED) as $prop) {
            $prop->setAccessible(true);

            if (!$prop->isInitialized($entity)) {
                continue;
            }

            $data[$prop->getName()] = $prop->getValue($entity);
        }

        return $data;
    }

    protected function getEntityProperties(): array
    {
        $reflection = new ReflectionClass($this->entityMap->getEntity());
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            $properties[$property->getName()] = $property->getDefaultValue() ?? null;
        }

        return $properties;
    }

    protected function getSkipAttributes(): array
    {
        return $this->skipAttributes;
    }

    private function validate(): void
    {
        if (null === $this->entityMap) {
            throw new RuntimeException('Entity map is not initialized.');
        }
    }

    public function getMappedModel(): EntityMap
    {
        return $this->getEntityMap();
    }

    public function getEntityMap(): EntityMap
    {
        return $this->entityMap;
    }

    public function setEntityMap(EntityMap $entityMap): void
    {
        $this->entityMap = $entityMap;
        $entityProperties = $this->getEntityProperties();

        foreach (array_keys($entityProperties) as $key) {
            if (in_array($key, $this->getSkipAttributes())) {
                continue;
            }

            unset($this->{$key});
        }

        foreach (array_filter($entityProperties) as $name => $defaultValue) {
            $this->{$name} = $defaultValue;
        }
    }

    public function __call(string $name, $args)
    {
        $this->validate();

        return $this->entityMap->{$name}(...$args);
    }

    public function __set(string $key, $value): void
    {
        $this->validate();

        if (in_array($key, $this->getSkipAttributes())) {
            $this->{$key} = $value;

            return;
        }

        if ($this->hasRelation($key)) {
            $this->bindRelation($key, $value);
            $this->relations[$key] = $value;
        } else {
            $this->entityMap->{$key} = $value;
        }
    }

    public function __get(string $key)
    {
        $this->validate();

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        $value = $this->entityMap->{$key};

        if ($value instanceof EntityMap) {
            $value = $value->hydrateToEntity();
        }

        return $value;
    }

    protected function hasRelation(string $key): bool
    {
        return method_exists($this->entityMap, $key) && $this->entityMap->{$key}() instanceof Relation;
    }

    protected function bindRelation(string $key, $value)
    {
        /** @var Relation $relation */
        $relation = $this->entityMap->{$key}();

        if (!$value instanceof LaravelCollection) {
            if ($value instanceof Entity && !$value instanceof MappableContract) {
                $mapperClass = sprintf('%sMap', get_class($value));
                $mappedClass = $mapperClass::getMappedClass();

                $value = $mappedClass::hydrateFromEntity($value);
                $value = $value->getMappedModel();
            } elseif (null !== $value && !$value instanceof MappableContract) {
                throw new InvalidArgumentException('Value should be an entity.');
            }

            $relation->associate($value);

            return;
        }

        if ($value instanceof Collection) {
            if ($relation instanceof BelongsTo) {
                $relation->attach($value);
            } elseif ($relation instanceof HasMany) {
                foreach ($value->getNewItems() as $item) {
                    $relation->add($item->getMappedModel());
                }

                $value->clearNewItems();
            }
        }
    }
}
