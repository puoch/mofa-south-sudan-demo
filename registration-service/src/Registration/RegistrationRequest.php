<?php

declare(strict_types=1);

namespace App\Registration;

use App\Validator\SyntheticEmail;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * What the public form submits. Which fields are required is pending Ministry
 * approval; for the prototype every confirmed field is required.
 *
 * Passport numbers and email addresses are restricted to synthetic formats so
 * that real details are rejected rather than stored.
 */
final class RegistrationRequest
{
    public const SYNTHETIC_PASSPORT_PATTERN = '/^SYN\d{6}$/';

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

    #[Assert\NotBlank(message: 'Enter a passport number.')]
    #[Assert\Regex(pattern: self::SYNTHETIC_PASSPORT_PATTERN, message: 'This prototype accepts only made-up passport numbers: SYN followed by six digits, like SYN123456.')]
    public ?string $passportNumber = null;

    #[Assert\NotBlank(message: 'Select the passport issuing country.')]
    #[Assert\Country]
    public ?string $passportIssuingCountry = null;

    #[Assert\NotNull(message: 'Enter the passport expiry date.')]
    public ?\DateTimeImmutable $passportExpiry = null;

    #[Assert\IsTrue(message: 'Confirm that every detail you entered is made up.')]
    public bool $syntheticConfirmation = false;

    #[Assert\IsTrue(message: 'Confirm that you have read the draft privacy notice.')]
    public bool $privacyAcknowledged = false;
}
