<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

use App\Accommodation\Domain\Entity\Accommodation;

final class AccommodationNotPublishableException extends \DomainException
{
    /**
     * @param non-empty-list<string> $missing machine-readable requirement keys still missing (e.g. 'title', 'photos')
     */
    public static function becauseRequirementsNotMet(array $missing): self
    {
        $labels = [
            'title' => 'a title',
            'description' => 'a description',
            'price' => 'a nightly price',
            'photos' => 'at least '.Accommodation::MIN_PHOTOS_TO_PUBLISH.' photos',
        ];

        $missingLabels = array_map(static fn (string $key): string => $labels[$key] ?? $key, $missing);

        return new self(\sprintf(
            'This accommodation cannot be published yet: it needs %s.',
            self::humanJoin($missingLabels),
        ));
    }

    /**
     * @param non-empty-list<string> $labels
     */
    private static function humanJoin(array $labels): string
    {
        if (1 === \count($labels)) {
            return $labels[0];
        }

        $last = array_pop($labels);

        return implode(', ', $labels).' and '.$last;
    }
}
