<?php

declare(strict_types=1);

namespace Pandawa\Reloquent\Repository;

use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select;
use Pandawa\Cycle\Repository\Repository as BaseRepository;
use Pandawa\Reloquent\EntityManager;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class Repository extends BaseRepository
{
    protected EntityManagerInterface $em;

    public function __construct(Select $select, ORMInterface $orm)
    {
        parent::__construct($select, $orm);

        $this->em = new EntityManager($orm);
    }
}
