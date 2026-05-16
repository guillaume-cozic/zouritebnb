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

    // --- SolidarityProject module (hexagonal architecture) ---

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\SolidarityProject\Domain'))
        ->should(new NotDependsOnTheseNamespaces([
            'App\SolidarityProject\Application',
            'App\SolidarityProject\Infrastructure',
        ]))
        ->because('the domain layer must not depend on application or infrastructure (hexagonal architecture)');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\SolidarityProject\Application'))
        ->should(new NotDependsOnTheseNamespaces(['App\SolidarityProject\Infrastructure']))
        ->because('the application layer must not depend on infrastructure (hexagonal architecture)');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\SolidarityProject\Domain', 'App\SolidarityProject\Application'))
        ->should(new NotDependsOnTheseNamespaces([
            'Doctrine',
            'ApiPlatform',
            'Symfony\Bundle',
            'Symfony\Component\HttpFoundation',
            'Symfony\Component\HttpKernel',
            'Symfony\Component\DependencyInjection',
        ]))
        ->because('domain and application layers must be framework-agnostic');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\SolidarityProject\Domain', 'App\SolidarityProject\Application'))
        ->should(new NotDependsOnTheseNamespaces(['App\Shared\Infrastructure']))
        ->because('domain and application layers must not depend on shared infrastructure');

    // --- Reservation module (hexagonal architecture) ---

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Reservation\Domain'))
        ->should(new NotDependsOnTheseNamespaces([
            'App\Reservation\Application',
            'App\Reservation\Infrastructure',
        ]))
        ->because('the domain layer must not depend on application or infrastructure (hexagonal architecture)');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Reservation\Application'))
        ->should(new NotDependsOnTheseNamespaces(['App\Reservation\Infrastructure']))
        ->because('the application layer must not depend on infrastructure (hexagonal architecture)');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Reservation\Domain', 'App\Reservation\Application'))
        ->should(new NotDependsOnTheseNamespaces([
            'Doctrine',
            'ApiPlatform',
            'Symfony\Bundle',
            'Symfony\Component\HttpFoundation',
            'Symfony\Component\HttpKernel',
            'Symfony\Component\DependencyInjection',
        ]))
        ->because('domain and application layers must be framework-agnostic');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Reservation\Domain', 'App\Reservation\Application'))
        ->should(new NotDependsOnTheseNamespaces(['App\Shared\Infrastructure']))
        ->because('domain and application layers must not depend on shared infrastructure');

    // --- Team module (hexagonal architecture) ---

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Team\Domain'))
        ->should(new NotDependsOnTheseNamespaces([
            'App\Team\Application',
            'App\Team\Infrastructure',
        ]))
        ->because('the domain layer must not depend on application or infrastructure (hexagonal architecture)');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Team\Application'))
        ->should(new NotDependsOnTheseNamespaces(['App\Team\Infrastructure']))
        ->because('the application layer must not depend on infrastructure (hexagonal architecture)');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Team\Domain', 'App\Team\Application'))
        ->should(new NotDependsOnTheseNamespaces([
            'Doctrine',
            'ApiPlatform',
            'Symfony\Bundle',
            'Symfony\Component\HttpFoundation',
            'Symfony\Component\HttpKernel',
            'Symfony\Component\DependencyInjection',
        ]))
        ->because('domain and application layers must be framework-agnostic');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Team\Domain', 'App\Team\Application'))
        ->should(new NotDependsOnTheseNamespaces(['App\Shared\Infrastructure']))
        ->because('domain and application layers must not depend on shared infrastructure');

    // --- Conversation module (hexagonal architecture) ---

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Conversation\Domain'))
        ->should(new NotDependsOnTheseNamespaces([
            'App\Conversation\Application',
            'App\Conversation\Infrastructure',
        ]))
        ->because('the domain layer must not depend on application or infrastructure (hexagonal architecture)');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Conversation\Application'))
        ->should(new NotDependsOnTheseNamespaces(['App\Conversation\Infrastructure']))
        ->because('the application layer must not depend on infrastructure (hexagonal architecture)');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Conversation\Domain', 'App\Conversation\Application'))
        ->should(new NotDependsOnTheseNamespaces([
            'Doctrine',
            'ApiPlatform',
            'Symfony\Bundle',
            'Symfony\Component\HttpFoundation',
            'Symfony\Component\HttpKernel',
            'Symfony\Component\DependencyInjection',
        ]))
        ->because('domain and application layers must be framework-agnostic');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Conversation\Domain', 'App\Conversation\Application'))
        ->should(new NotDependsOnTheseNamespaces(['App\Shared\Infrastructure']))
        ->because('domain and application layers must not depend on shared infrastructure');

    // --- Vertical slicing ---

    // Shared must not depend on any module
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Shared'))
        ->should(new NotDependsOnTheseNamespaces(['App\Accommodation', 'App\SolidarityProject', 'App\Reservation', 'App\Team', 'App\Conversation']))
        ->because('the shared layer must not depend on any module');

    // Modules must not depend on each other
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Accommodation'))
        ->should(new NotDependsOnTheseNamespaces(['App\SolidarityProject', 'App\Reservation', 'App\Team', 'App\Conversation']))
        ->because('modules must not depend on each other (vertical slicing)');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\SolidarityProject'))
        ->should(new NotDependsOnTheseNamespaces(['App\Accommodation', 'App\Reservation', 'App\Team', 'App\Conversation']))
        ->because('modules must not depend on each other (vertical slicing)');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Reservation'))
        ->should(new NotDependsOnTheseNamespaces(['App\Accommodation', 'App\SolidarityProject', 'App\Team', 'App\Conversation']))
        ->because('modules must not depend on each other (vertical slicing)');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Team'))
        ->should(new NotDependsOnTheseNamespaces(['App\Accommodation', 'App\SolidarityProject', 'App\Reservation', 'App\Conversation']))
        ->because('modules must not depend on each other (vertical slicing)');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Conversation'))
        ->should(new NotDependsOnTheseNamespaces(['App\Accommodation', 'App\SolidarityProject', 'App\Reservation', 'App\Team']))
        ->because('modules must not depend on each other (vertical slicing)');

    $config->add($classSet, ...$rules);
};
