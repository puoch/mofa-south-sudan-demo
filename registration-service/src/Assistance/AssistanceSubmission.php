<?php

declare(strict_types=1);

namespace App\Assistance;

use App\Validator\SyntheticEmail;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * What the public distress and enquiry form submits.
 *
 * Contact details follow the same synthetic-only rules as registration. The
 * description and the optional location details are free text, so they
 * cannot be checked for real information: the confirmation checkbox and the
 * warnings on the page are the guard, and both are encrypted before storage.
 */
final class AssistanceSubmission
{
    public const TYPE_DISTRESS = 'distress';
    public const TYPE_ENQUIRY = 'enquiry';

    #[Assert\NotBlank(message: 'Select whether you need help or have an enquiry.')]
    #[Assert\Choice(choices: [self::TYPE_DISTRESS, self::TYPE_ENQUIRY], message: 'Select whether you need help or have an enquiry.')]
    public ?string $requestType = null;

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

    #[Assert\NotBlank(message: 'Describe your situation or enquiry.')]
    #[Assert\Length(max: 4000, maxMessage: 'Keep the description to 4,000 characters or fewer.')]
    public ?string $message = null;

    #[Assert\NotBlank(message: 'Select the country you are in now.')]
    #[Assert\Country]
    public ?string $country = null;

    #[Assert\NotBlank(message: 'Enter the city or town you are in now.')]
    #[Assert\Length(max: 100)]
    public ?string $city = null;

    #[Assert\Length(max: 500, maxMessage: 'Keep the location details to 500 characters or fewer.')]
    public ?string $locationDetails = null;

    #[Assert\IsTrue(message: 'Confirm that every detail you entered is made up.')]
    public bool $syntheticConfirmation = false;

    #[Assert\IsTrue(message: 'Confirm that you understand this is not an emergency service.')]
    public bool $notEmergencyAcknowledged = false;

    public function hasLocationDetails(): bool
    {
        return trim($this->locationDetails ?? '') !== '';
    }
}
