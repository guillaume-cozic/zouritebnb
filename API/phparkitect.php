<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/src');

    $rules = [];

    // --- Hexagonal architecture ---

    // Domain must not depend on Application or Infrastructure
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Accommodation\Domain'))
        ->should(new NotDependsOnTheseNamespaces([
            'App\Accommodation\Application',
            'App\Accommodation\Infrastructure',
        ]))
        ->because('the domain layer must not depend on application or infrastructure (hexagonal architecture)');

    // Application must not depend on Infrastructure
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Accommodation\Application'))
        ->should(new NotDependsOnTheseNamespaces(['App\Accommodation\Infrastructure']))
        ->because('the application layer must not depend on infrastructure (hexagonal architecture)');

    // Application must stay framework-agnostic
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Accommodation\Application'))
        ->should(new NotDependsOnTheseNamespaces([
            'Doctrine',
            'ApiPlatform',
            'Symfony\Bundle',
            'Symfony\Component\HttpFoundation',
            'Symfony\Component\HttpKernel',
            'Symfony\Component\DependencyInjection',
        ]))
        ->because('the application layer must be framework-agnostic');

    // Domain must stay framework-agnostic
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Accommodation\Domain'))
        ->should(new NotDependsOnTheseNamespaces([
            'Doctrine',
            'ApiPlatform',
            'Symfony\Bundle',
            'Symfony\Component\HttpFoundation',
            'Symfony\Component\HttpKernel',
            'Symfony\Component\DependencyInjection',
        ]))
        ->because('the domain layer must be framework-agnostic');

    // Domain can depend on App\Shared\Domain but not on Shared Infrastructure
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Accommodation\Domain'))
        ->should(new NotDependsOnTheseNamespaces(['App\Shared\Infrastructure']))
        ->because('the domain layer must not depend on shared infrastructure');

    // Application must not depend on Shared Infrastructure
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Accommodation\Application'))
        ->should(new NotDependsOnTheseNamespaces(['App\Shared\Infrastructure']))
        ->because('the application layer must not depend on shared infrastructure');

    // --- Vertical slicing ---

    // Shared must not depend on any module
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Shared'))
        ->should(new NotDependsOnTheseNamespaces(['App\Accommodation']))
        ->because('the shared layer must not depend on any module');

    // Modules must not depend on each other (only on Shared and own namespace)
    // Add rules here as new modules are introduced, e.g.:
    // $rules[] = Rule::allClasses()
    //     ->that(new ResideInOneOfTheseNamespaces(['App\Accommodation']))
    //     ->should(new NotDependsOnTheseNamespaces(['App\Booking']))
    //     ->because('modules must not depend on each other (vertical slicing)');

    $config->add($classSet, ...$rules);
};
