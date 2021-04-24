<?php

declare(strict_types=1);

namespace Pandawa\Reloquent\Entity;

use Pandawa\Reloquent\Map\Mappable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class EntityFactory
{
    /**
     * @param string $entity
     *
     * @return Mappable
     */
    public static function create(string $entity): mixed
    {
        $entityMap = $entity . 'Map';
        $proxyClass = $entityMap::{'getMappedClass'}();

        return new $proxyClass;
    }
}
