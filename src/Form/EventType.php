<?php

namespace App\Form;

use App\Entity\Event;
use App\Validator\DateConstraint;
use DateTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description', null, [
                'required' => true
            ])
            ->add('startDate', null, [
                'widget' => 'single_text',
                'attr' => [
                    'min' => (new DateTime('+7day'))->format('Y-m-d h:i')
                ],
            ])
            ->add('maxParticipants')
            ->add('isPublic', null, [
                'label' => 'Event public'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'constraints' => [
                new DateConstraint('startDate')
            ]
        ]);
    }
}
