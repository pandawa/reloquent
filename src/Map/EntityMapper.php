<?php

declare(strict_types=1);

namespace Pandawa\Reloquent\Map;

use Illuminate\Filesystem\Filesystem;
use Pandawa\Reloquent\Contract\Mappable as MappableContract;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class EntityMapper
{
    public function __construct(private Filesystem $files, private string $entityPath)
    {
        $this->makeDirectory($this->entityPath);
    }

    public static function create(): static
    {
        return new static(new Filesystem(), base_path('bootstrap/cache/entities'));
    }

    public function createEntity(string $entityClass, bool $withConstructor = true): MappableContract
    {
        $className = $this->createClassIfNeeded($entityClass);
        $reflectionClass = new \ReflectionClass($className);

        if ($withConstructor) {
            return $reflectionClass->newInstance();
        }

        return $reflectionClass->newInstanceWithoutConstructor();
    }

    public function createClassIfNeeded(string $entityClass): string
    {
        $className = $this->getClassName($entityClass);

        if (!class_exists($className)) {
            $this->buildClass($entityClass);
        } else {
            if (in_array(app()->environment(), ['dev', 'local'])) {
                $this->buildClass($entityClass);
            }
        }

        return $className;
    }

    public function buildClass(string $entityClass): void
    {
        $mapClass = $entityClass . 'Map';
        $className = $this->getClassName($entityClass);
        $filename = $className  . '.php';
        $path = $this->entityPath . '/' . $filename;
        $stub = $this->files->get(__DIR__ . '/../stubs/entity-proxy.stub');

        $stub = $this->replaceClassName($stub, $entityClass);
        $stub = $this->replaceExtend($stub, $entityClass);
        $stub = $this->replaceEntityMapClassName($stub, $mapClass);

        $this->makeDirectory($path);

        $this->files->put($path, $stub);

        $loader = require base_path('vendor/autoload.php');
        $loader->addClassMap([$className => $path]);
    }

    private function makeDirectory(string $path): string
    {
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }

    private function replaceClassName(string $stub, string $entityClass)
    {
        return str_replace('{{ class }}', $this->getClassName($entityClass), $stub);
    }

    private function replaceEntityMapClassName(string $stub, string $entityMapClass)
    {
        return str_replace('{{ entity_map_class }}', $entityMapClass, $stub);
    }

    private function replaceExtend(string $stub, string $entityClass): string
    {
        return str_replace('{{ entity }}', $entityClass, $stub);
    }

    private function getClassName(string $entityClass): string
    {
        return str_replace('\\', '_', $entityClass);
    }
}
