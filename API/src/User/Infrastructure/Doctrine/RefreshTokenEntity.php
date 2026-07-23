<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshTokenRepository;
use Gesdinet\JWTRefreshTokenBundle\Model\AbstractRefreshToken;

/**
 * Persisted refresh token backing the "stay signed in" flow.
 *
 * The parent {@see AbstractRefreshToken} declares the properties; we redeclare
 * them here only to attach the Doctrine attribute mapping (the app maps entities
 * by attributes, not the bundle's shipped XML).
 */
#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
#[ORM\Table(name: 'refresh_tokens')]
class RefreshTokenEntity extends AbstractRefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected int|string|null $id = null;

    #[ORM\Column(name: 'refresh_token', type: 'string', length: 128, unique: true)]
    protected ?string $refreshToken = null;

    #[ORM\Column(type: 'string', length: 255)]
    protected ?string $username = null;

    #[ORM\Column(type: 'datetime')]
    protected ?\DateTimeInterface $valid = null;
}
