<?php

declare(strict_types=1);

namespace Pandawa\Reloquent;

use Pandawa\Component\Ddd\Repository\EntityManagerInterface;
use Pandawa\Component\Module\AbstractModule;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class PandawaReloquentModule extends AbstractModule
{
    protected function build(): void
    {
        $this->app->singleton(EntityManagerInterface::class, function ($app) {
            $emManagerClass = config('modules.ddd.entity_manager_class');

            return new $emManagerClass($app, Repository::class);
        });
    }
}
