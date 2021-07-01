<?php

declare(strict_types=1);

namespace Pandawa\Reloquent;

use Pandawa\Component\Ddd\Model;
use Pandawa\Component\Ddd\Repository\Repository as BaseRepository;
use Pandawa\Reloquent\Contract\AggregateRoot;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class Repository extends BaseRepository
{
    public function save(&$model)
    {
        if ($model instanceof Model) {
            return parent::save($model);
        }

        $model = $this->hydrate($model);

        return parent::save($model);
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
