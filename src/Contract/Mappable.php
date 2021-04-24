<?php

declare(strict_types=1);

namespace Pandawa\Reloquent\Contract;

use Pandawa\Reloquent\Map\EntityMap;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface Mappable
{
    public function setEntityMap(EntityMap $entityMap): void;

    public function getEntityMap(): EntityMap;
}
