<?php

declare(strict_types=1);

namespace Pandawa\Reloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pandawa\Component\Ddd\AbstractModel;
use Pandawa\Component\Ddd\Model;
use Pandawa\Component\Ddd\Repository\Repository as BaseRepository;
use Pandawa\Reloquent\Contract\AggregateRoot;
use Pandawa\Reloquent\Contract\Mappable;

/**
 * @template T
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class Repository extends BaseRepository
{
    /**
     * @param T $model
     *
     * @return T
     */
    public function save(&$model)
    {
        if ($model instanceof Model) {
            return parent::save($model);
        }

        $model = $this->hydrate($model);

        return parent::save($model);
    }

    /**
     * @param mixed    $id
     * @param int|null $lockMode
     *
     * @return T|null
     */
    public function find($id, int $lockMode = null)
    {
        return parent::find($id, $lockMode);
    }

    /**
     * @param array $criteria
     *
     * @return T|null
     */
    public function findOneBy(array $criteria)
    {
        return parent::findOneBy($criteria);
    }

    /**
     * @param array $criteria
     *
     * @return LengthAwarePaginator|Collection<T>|T[]
     */
    public function findBy(array $criteria)
    {
        return parent::findBy($criteria);
    }

    /**
     * @return LengthAwarePaginator|Collection<T>|T[]
     */
    public function findAll()
    {
        return parent::findAll();
    }

    protected function persist($model, string $walker = null): bool
    {
        if (null === $walker) {
            $walker = uniqid();
            $this->queuing = [];
        }

        $this->queuing[$walker][spl_object_hash($model)] = true;
        foreach ($model->getRelations() as $entities) {
            $entities = $entities instanceof Collection ? $entities->all() : [$entities];

            /** @var AbstractModel $item */
            foreach (array_filter($entities) as $item) {
                if (isset($this->queuing[$walker][spl_object_hash($item)])) {
                    $this->invokeSaveModel($item);
                }

                if ($item instanceof AbstractModel || $item instanceof Mappable) {
                    $this->persist($item, $walker);
                }
            }
        }

        if (null === $walker) {
            unset($this->queuing[$walker]);
        }

        if (!$this->invokeSaveModel($model)) {
            return false;
        }

        return true;
    }

    protected function hydrate($model): mixed
    {
        $entityClass = $this->getModelClass();

        return $entityClass::hydrateFromEntity($model);
    }

    protected function invokeSaveModel($model): bool
    {
        return tap(parent::invokeSaveModel($model), fn() => $this->dispatchEvents($model));
    }

    protected function invokeDeleteModel($model): bool
    {
        return tap(parent::invokeDeleteModel($model), fn() => $this->dispatchEvents($model));
    }

    protected function dispatchEvents($model): void
    {
        if ($model instanceof AggregateRoot) {
            foreach ($model->pullEvents() as $event) {
                event($event);
            }
        }
    }
}
