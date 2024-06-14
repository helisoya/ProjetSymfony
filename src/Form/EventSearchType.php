<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title',null, [
                'required' => false
            ])
            ->add('description',null, [
                'required' => false
            ])
            ->add('minStartDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'data' => new \DateTime('-20 year')
            ])
            ->add('maxStartDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'data' => new \DateTime('+100 year')
            ])
            ->add('maxParticipants',null, [
                'required' => false
            ])
            ->add('isPublic',null, [
                'required' => false
            ])
            ->add('creator', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
                'placeholder' => 'Choose an option',
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {

    }
}
