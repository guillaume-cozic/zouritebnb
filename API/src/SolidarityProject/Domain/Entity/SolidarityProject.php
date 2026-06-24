<?php

declare(strict_types=1);

namespace App\SolidarityProject\Domain\Entity;

use App\SolidarityProject\Domain\Exception\InvalidSolidarityProjectException;
use Symfony\Component\Uid\Uuid;

final readonly class SolidarityProject
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    public const DEFAULT_LOCALE = 'fr';
    public const SUPPORTED_LOCALES = ['fr', 'en'];

    private const ALLOWED_STATUSES = [self::STATUS_ACTIVE, self::STATUS_CLOSED];

    private ?string $imageUrl;

    /**
     * @var array<string, ProjectTranslation> the translatable content keyed by locale,
     *                                        always containing at least the default locale
     */
    private array $translations;

    /**
     * @param array<string, ProjectTranslation> $translations
     */
    public function __construct(
        private Uuid $id,
        array $translations,
        ?string $imageUrl,
        private string $status,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        private bool $isDefault = false,
    ) {
        if (!isset($translations[self::DEFAULT_LOCALE])) {
            throw InvalidSolidarityProjectException::becauseDefaultTranslationIsMissing(self::DEFAULT_LOCALE);
        }

        foreach ($translations as $locale => $translation) {
            if (!\in_array($locale, self::SUPPORTED_LOCALES, true)) {
                throw InvalidSolidarityProjectException::becauseLocaleIsUnsupported((string) $locale);
            }

            if (!$translation instanceof ProjectTranslation) {
                throw InvalidSolidarityProjectException::becauseTranslationIsInvalid((string) $locale);
            }
        }

        if (!\in_array($status, self::ALLOWED_STATUSES, true)) {
            throw InvalidSolidarityProjectException::becauseStatusIsInvalid($status);
        }

        if (null !== $imageUrl) {
            $imageUrl = trim($imageUrl);
            if ('' === $imageUrl) {
                throw InvalidSolidarityProjectException::becauseImageUrlIsBlank();
            }
        }

        $this->translations = $translations;
        $this->imageUrl = $imageUrl;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    /**
     * @return array<string, ProjectTranslation>
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }

    /**
     * Returns the content for the requested locale, falling back to the default
     * locale when the project has not been translated into it.
     */
    public function translation(string $locale): ProjectTranslation
    {
        return $this->translations[$locale] ?? $this->translations[self::DEFAULT_LOCALE];
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }
}
