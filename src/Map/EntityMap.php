<?php

declare(strict_types=1);

namespace Pandawa\Reloquent\Map;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\Support\Str;
use Pandawa\Component\Ddd\AbstractModel;
use Pandawa\Component\Ddd\CollectionInterface;
use Pandawa\Reloquent\Builder;
use Pandawa\Reloquent\Collection;
use Pandawa\Reloquent\Contract\Mappable as MappableContract;
use ReflectionObject;
use RuntimeException;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
abstract class EntityMap extends AbstractModel
{
    public $incrementing = false;

    protected static ?string $entity = null;
    protected mixed $lastEntity;

    public static function getMappedClass(): string
    {
        return EntityMapper::create()->createClassIfNeeded(static::getEntity());
    }

    public static function getModelClass(): string
    {
        if (null !== $modelClass = static::$modelClass) {
            return $modelClass;
        }

        return preg_replace('/(Map|EntityMap)$/', '', get_called_class());
    }

    public static function getEntity(): string
    {
        if (null !== static::$entity) {
            return static::$entity;
        }

        return preg_replace('/(Map|EntityMap)$/', '', get_called_class());
    }

    public function getTable(): string
    {
        if (null !== $this->table) {
            return $this->table;
        }

        $className = preg_replace('/(Map|EntityMap)$/', '', class_basename($this));

        return Str::snake(Str::pluralStudly($className));
    }

    public function newFromBuilder($attributes = [], $connection = null): mixed
    {
        return $this->createEntity(parent::newFromBuilder($attributes, $connection));
    }

    public function hydrateToEntity(): mixed
    {
        return $this->newFromBuilder($this->attributes);
    }

    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    public function newCollection(array $entities = []): CollectionInterface
    {
        return new Collection($entities);
    }

    protected function setDomainEventsAttribute(): void
    {
        // Skip set domain events attribute
    }

    protected function serializeRelationship(array $parents): array
    {
        $attributes = [];

        foreach ($this->getArrayableRelations() as $key => $value) {
            if (!$value || isset($parents[spl_object_hash($value)])) {
                continue;
            }

            $temp = null;

            if ($value instanceof MappableContract) {
                $value = $value->getEntityMap();
            }

            if ($value instanceof LaravelCollection) {
                $temp = [];
                $value->each(
                    function ($model) use (&$temp, $parents, $value) {
                        if ($model instanceof MappableContract) {
                            $model = $model->getEntityMap();
                        }

                        if ($model instanceof AbstractModel) {
                            $temp[] = $model->serialize(array_merge($parents, [spl_object_hash($value) => true]));

                            return;
                        }

                        if ($model instanceof Arrayable) {
                            $temp[] = $model->toArray();

                            return;
                        }

                        $temp[] = $model;
                    }
                );
            } else {
                if ($value instanceof AbstractModel) {
                    $temp = $value->serialize($parents);
                } else {
                    if ($value instanceof Arrayable) {
                        $temp = parent::toArray();
                    }
                }
            }

            $attributes[$key] = $temp;
        }

        return $attributes;
    }

    protected function createEntity($map)
    {
        $entity = EntityMapper::create()->createEntity(static::getEntity());

        $this->validateEntity($entity);

        $entity->setEntityMap($map);

        return $entity;
    }

    protected function validateEntity($entityObject): void
    {
        $reflection = new ReflectionObject($entityObject);

        if (!$reflection->isSubclassOf(static::getEntity())) {
            throw new RuntimeException(
                sprintf(
                    'Entity "%s" should instance of "%s"',
                    get_class($entityObject),
                    static::getEntity()
                )
            );
        }

        if (!$entityObject instanceof MappableContract) {
            throw new RuntimeException('Entity proxy should be instance of MappableContract');
        }
    }
}
