<?php

declare(strict_types=1);

namespace Pandawa\Reloquent;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class Builder extends EloquentBuilder
{
    protected function eagerLoadRelation(array $models, $name, Closure $constraints)
    {
        $models = array_map(fn($model) => $model->getMappedModel(), $models);
        $relation = $this->getRelation($name);

        $relation->addEagerConstraints($models);

        $constraints($relation);

        return $relation->match(
            $relation->initRelation($models, $name),
            $relation->getEager(), $name
        );
    }

}
