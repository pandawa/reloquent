<?php

declare(strict_types=1);

namespace Pandawa\Reloquent\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Pandawa\Component\Module\AbstractModule;
use Pandawa\Reloquent\Entity\Entity;
use Pandawa\Reloquent\Map\EntityMapper;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class CacheEntityConsole extends Command
{
    protected $signature = 'reloquent:cache';

    protected $description = 'Cache reloquent entities.';

    public function handle(): void
    {
        $mapper = EntityMapper::create();

        foreach ($this->getAllServiceProviders() as $serviceProvider) {
            if ($serviceProvider instanceof AbstractModule) {
                $path = $this->getModulePath($serviceProvider) . '/' . $this->getModelPath($serviceProvider);

                if (is_dir($path)) {
                    foreach (Finder::create()->in($path) as $file) {
                        $className = $this->getClassFromFile($serviceProvider, $file);

                        if (is_subclass_of($className, Entity::class)) {
                            $mapper->buildClass($className);
                        }
                    }
                }
            }
        }

        File::put($mapper->getEntityPath() . '/.cache', '');
    }

    private function getClassFromFile(AbstractModule $module, SplFileInfo $file): string
    {
        $className = $this->getNamespace($module) . '\\' . str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($file->getPathname(), $this->getModulePath($module) . '/')
            );

        return preg_replace('/\\+/', '\\', $className);
    }

    private function getAllServiceProviders(): array
    {
        $getAllServiceProviders = \Closure::bind(function () {
            return $this->serviceProviders;
        }, app(), app());

        return $getAllServiceProviders();
    }

    private function getModulePath(AbstractModule $module): string
    {
        return \Closure::bind(fn() => $this->getCurrentPath(), $module, $module)();
    }

    private function getModelPath(AbstractModule $module): string
    {
        return \Closure::bind(fn() => $this->modelPathName, $module, $module)();
    }

    private function getNamespace(AbstractModule $module): string
    {
        return \Closure::bind(fn() => $this->getNamespace(), $module, $module)();
    }
}
