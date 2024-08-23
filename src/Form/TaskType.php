<?php

namespace App\Form;

use App\Entity\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('createdAt', null, [
                'widget' => 'single_text',
                'label' => 'Date de création',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('title', null, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank(['message' => 'Le titre ne peut pas être vide.']),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('content', null, [
                'label' => 'Contenu',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('isDone', null, [
                'label' => 'Terminé',
                'attr' => ['class' => 'form-check-input'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}
