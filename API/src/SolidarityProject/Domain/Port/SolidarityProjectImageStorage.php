<?php

declare(strict_types=1);

namespace App\SolidarityProject\Domain\Port;

interface SolidarityProjectImageStorage
{
    public function store(string $filename, string $content): void;
}
