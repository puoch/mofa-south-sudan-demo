<?php

declare(strict_types=1);

namespace App\Registration;

use App\Validator\SyntheticEmail;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * What the public form submits. Which fields are required is pending Ministry
 * approval. Passport details and the national ID number are both optional,
 * as the project owner asked on 24 October 2026; if any passport field is
 * given, all three are needed.
 *
 * Passport numbers and email addresses are restricted to synthetic formats so
 * that real details are rejected rather than stored.
 */
final class RegistrationRequest
{
    public const SYNTHETIC_PASSPORT_PATTERN = '/^SYN\d{6}$/i';
    public const SYNTHETIC_NATIONAL_ID_PATTERN = '/^SYNID\d{6}$/i';

    #[Assert\NotBlank(message: 'Enter a full name.')]
    #[Assert\Length(max: 200)]
    public ?string $fullName = null;

    #[Assert\NotBlank(message: 'Enter an email address.')]
    #[Assert\Email(mode: Assert\Email::VALIDATION_MODE_HTML5, message: 'Enter an email address in the correct format, like name@example.org.')]
    #[Assert\Length(max: 254)]
    #[SyntheticEmail]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Enter a phone number.')]
    #[Assert\Regex(pattern: '/^\+[1-9]\d{6,14}$/', message: 'Enter the phone number in international format, starting with + and the country code, with no spaces.')]
    public ?string $phone = null;

    #[Assert\NotBlank(message: 'Select a nationality.')]
    #[Assert\Country]
    public ?string $nationality = null;

    #[Assert\NotBlank(message: 'Select a country of residence.')]
    #[Assert\Country]
    public ?string $countryOfResidence = null;

    #[Assert\NotBlank(message: 'Enter a city of residence.')]
    #[Assert\Length(max: 100)]
    public ?string $cityOfResidence = null;

    #[Assert\NotNull(message: 'Enter a date of birth.')]
    #[Assert\LessThan('today', message: 'Date of birth must be in the past.')]
    #[Assert\GreaterThan('1900-01-01', message: 'Enter a real date of birth.')]
    public ?\DateTimeImmutable $dateOfBirth = null;

    #[Assert\Regex(pattern: self::SYNTHETIC_PASSPORT_PATTERN, message: 'This prototype accepts only made-up passport numbers: SYN followed by six digits, like SYN123456.')]
    public ?string $passportNumber = null;

    #[Assert\Country]
    public ?string $passportIssuingCountry = null;

    public ?\DateTimeImmutable $passportExpiry = null;

    #[Assert\Regex(pattern: self::SYNTHETIC_NATIONAL_ID_PATTERN, message: 'This prototype accepts only made-up national ID numbers: SYNID followed by six digits, like SYNID123456.')]
    public ?string $nationalIdNumber = null;

    #[Assert\IsTrue(message: 'Confirm that every detail you entered is made up.')]
    public bool $syntheticConfirmation = false;

    #[Assert\IsTrue(message: 'Confirm that you have read the draft privacy notice.')]
    public bool $privacyAcknowledged = false;

    public function hasNationalId(): bool
    {
        return ($this->nationalIdNumber ?? '') !== '';
    }

    public function hasPassport(): bool
    {
        return ($this->passportNumber ?? '') !== '' || ($this->passportIssuingCountry ?? '') !== '' || $this->passportExpiry !== null;
    }

    /**
     * Passport details are optional, but a partial set is not useful.
     */
    #[Assert\Callback]
    public function validatePassportSet(ExecutionContextInterface $context): void
    {
        if (!$this->hasPassport()) {
            return;
        }
        $missing = [
            'passportNumber' => [($this->passportNumber ?? '') === '', 'Enter the passport number, or leave all passport fields empty.'],
            'passportIssuingCountry' => [($this->passportIssuingCountry ?? '') === '', 'Select the passport issuing country, or leave all passport fields empty.'],
            'passportExpiry' => [$this->passportExpiry === null, 'Enter the passport expiry date, or leave all passport fields empty.'],
        ];
        foreach ($missing as $field => [$isMissing, $message]) {
            if ($isMissing) {
                $context->buildViolation($message)->atPath($field)->addViolation();
            }
        }
    }
}
