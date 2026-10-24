<?php

declare(strict_types=1);

namespace App\Registration;

use App\Entity\Registration;
use App\Security\FieldCipher;

/**
 * Prepares a record for the staff console.
 *
 * Identity document numbers are masked unless the caller asks to reveal
 * them, which controllers allow only for roles permitted to reveal and only
 * after writing an audit entry.
 */
final class RecordPresenter
{
    public function __construct(private readonly FieldCipher $cipher)
    {
    }

    /**
     * @return array<string, string|null>
     */
    public function sensitive(Registration $registration, bool $reveal): array
    {
        $s = $registration->getSensitive();
        if ($s === null) {
            return [];
        }
        $passport = $s->getPassportNumber() !== null ? $this->cipher->decrypt($s->getPassportNumber(), 'passport_number') : null;
        $nationalId = $s->getNationalIdNumber() !== null ? $this->cipher->decrypt($s->getNationalIdNumber(), 'national_id_number') : null;

        return [
            'dateOfBirth' => $this->cipher->decrypt($s->getDateOfBirth(), 'date_of_birth'),
            'passportNumber' => $passport === null ? null : ($reveal ? $passport : self::mask($passport)),
            'passportIssuingCountry' => $s->getPassportIssuingCountry() !== null ? $this->cipher->decrypt($s->getPassportIssuingCountry(), 'passport_issuing_country') : null,
            'passportExpiry' => $s->getPassportExpiry() !== null ? $this->cipher->decrypt($s->getPassportExpiry(), 'passport_expiry') : null,
            'nationalIdNumber' => $nationalId === null ? null : ($reveal ? $nationalId : self::mask($nationalId)),
        ];
    }

    public function hasIdentityDocument(Registration $registration): bool
    {
        $s = $registration->getSensitive();

        return $s !== null && ($s->getPassportNumber() !== null || $s->getNationalIdNumber() !== null);
    }

    /**
     * Keeps the last two characters, for example "•••••••56".
     */
    public static function mask(string $value): string
    {
        return str_repeat('•', max(0, mb_strlen($value) - 2)).mb_substr($value, -2);
    }
}
