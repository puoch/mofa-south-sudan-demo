<?php

declare(strict_types=1);

namespace App\Form;

use App\Registration\RegistrationRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Blank;

/**
 * The public registration form.
 *
 * The country list is the ISO 3166 list from ICU. It is a placeholder: the
 * Ministry has not chosen which list of countries and territories to use.
 */
final class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $country = ['placeholder' => 'Select a country', 'preferred_choices' => ['SS']];
        $date = ['widget' => 'single_text', 'input' => 'datetime_immutable'];

        $builder
            ->add('fullName', TextType::class, ['label' => 'Full name', 'attr' => ['autocomplete' => 'off']])
            ->add('email', EmailType::class, ['label' => 'Email address', 'help' => 'Use a made-up address such as amani@example.org.', 'attr' => ['autocomplete' => 'off', 'spellcheck' => 'false']])
            ->add('phone', TelType::class, ['label' => 'Phone number', 'help' => 'International format, for example +15550100123.', 'attr' => ['autocomplete' => 'off']])
            ->add('nationality', CountryType::class, ['label' => 'Nationality'] + $country)
            ->add('countryOfResidence', CountryType::class, ['label' => 'Country of residence'] + $country)
            ->add('cityOfResidence', TextType::class, ['label' => 'City of residence', 'attr' => ['autocomplete' => 'off']])
            ->add('dateOfBirth', DateType::class, ['label' => 'Date of birth'] + $date)
            ->add('passportNumber', TextType::class, ['label' => 'Passport number', 'help' => 'Prototype only: use SYN followed by six digits, such as SYN123456.', 'attr' => ['autocomplete' => 'off', 'spellcheck' => 'false']])
            ->add('passportIssuingCountry', CountryType::class, ['label' => 'Passport issuing country'] + $country)
            ->add('passportExpiry', DateType::class, ['label' => 'Passport expiry date'] + $date)
            ->add('syntheticConfirmation', CheckboxType::class, ['label' => 'I confirm that every detail I entered is made up and does not belong to a real person.'])
            ->add('privacyAcknowledged', CheckboxType::class, ['label' => 'I have read the draft privacy notice.'])
            // Honeypot: hidden from people, often filled in by bots.
            ->add('website', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Leave this field empty',
                'constraints' => [new Blank()],
                'row_attr' => ['class' => 'visually-hidden', 'aria-hidden' => 'true'],
                'attr' => ['tabindex' => '-1', 'autocomplete' => 'off'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegistrationRequest::class,
            'csrf_token_id' => 'registration',
        ]);
    }
}
