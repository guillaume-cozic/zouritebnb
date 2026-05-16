<?php

declare(strict_types=1);

namespace App\Team\Domain\Entity;

use App\Team\Domain\Exception\InvalidBankAccountException;

final readonly class Iban
{
    private string $normalized;

    public function __construct(private ?string $value)
    {
        $this->normalized = $this->validateAndNormalize();
    }

    private function validateAndNormalize(): string
    {
        if (null === $this->value || '' === trim($this->value)) {
            throw InvalidBankAccountException::becauseIbanEmpty();
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', $this->value) ?? '');

        if (1 !== preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $normalized)) {
            throw InvalidBankAccountException::becauseIbanFormatInvalid($this->value);
        }

        $rearranged = substr($normalized, 4).substr($normalized, 0, 4);
        $numeric = '';
        for ($i = 0, $len = \strlen($rearranged); $i < $len; ++$i) {
            $ch = $rearranged[$i];
            $numeric .= ctype_alpha($ch) ? (string) (\ord($ch) - 55) : $ch;
        }

        if (1 !== self::mod97($numeric)) {
            throw InvalidBankAccountException::becauseIbanChecksumInvalid($this->value);
        }

        return $normalized;
    }

    private static function mod97(string $digits): int
    {
        $remainder = 0;
        for ($i = 0, $len = \strlen($digits); $i < $len; ++$i) {
            $remainder = ($remainder * 10 + (int) $digits[$i]) % 97;
        }

        return $remainder;
    }

    public function value(): string
    {
        return $this->normalized;
    }

    public function masked(): string
    {
        $len = \strlen($this->normalized);
        if ($len <= 8) {
            return $this->normalized;
        }

        return substr($this->normalized, 0, 4).str_repeat('•', $len - 8).substr($this->normalized, -4);
    }
}
