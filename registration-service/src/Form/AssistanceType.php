<?php

declare(strict_types=1);

namespace App\Form;

use App\Assistance\AssistanceSubmission;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Blank;

/**
 * The public distress and enquiry form. Location is typed in: the browser
 * is not asked for the device's position.
 */
final class AssistanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('requestType', ChoiceType::class, [
                'label' => 'What do you need?',
                'expanded' => true,
                'choices' => [
                    'I am in distress and need help' => AssistanceSubmission::TYPE_DISTRESS,
                    'I have an enquiry' => AssistanceSubmission::TYPE_ENQUIRY,
                ],
                'placeholder' => false,
            ])
            ->add('fullName', TextType::class, ['label' => 'Full name', 'attr' => ['autocomplete' => 'off']])
            ->add('email', EmailType::class, ['label' => 'Email address', 'help' => 'Use a made-up address such as amani@example.org.', 'attr' => ['autocomplete' => 'off', 'spellcheck' => 'false']])
            ->add('phone', TelType::class, ['label' => 'Phone number', 'help' => 'International format, for example +15550100123.', 'attr' => ['autocomplete' => 'off']])
            ->add('message', TextareaType::class, ['label' => 'Describe your situation or enquiry', 'help' => 'Say what has happened and what help you need. Use made-up details only. Up to 4,000 characters.', 'attr' => ['rows' => 8, 'maxlength' => 4000]])
            ->add('country', CountryType::class, ['label' => 'Country you are in now', 'placeholder' => 'Select a country'])
            ->add('city', TextType::class, ['label' => 'City or town', 'attr' => ['autocomplete' => 'off']])
            ->add('locationDetails', TextareaType::class, ['label' => 'More about where you are (optional)', 'required' => false, 'help' => 'For example a district, landmark or the name of a building. Up to 500 characters.', 'attr' => ['rows' => 3, 'maxlength' => 500]])
            ->add('syntheticConfirmation', CheckboxType::class, ['label' => 'I confirm that every detail I entered is made up and does not belong to a real person.'])
            ->add('notEmergencyAcknowledged', CheckboxType::class, ['label' => 'I understand that this demonstration is not an emergency service and that no one will respond to this request.'])
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
            'data_class' => AssistanceSubmission::class,
            'csrf_token_id' => 'assistance',
        ]);
    }
}
