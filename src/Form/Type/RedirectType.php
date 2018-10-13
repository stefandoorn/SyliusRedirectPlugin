<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class RedirectType extends AbstractResourceType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('source', TextType::class, [
                'label' => 'setono_sylius_redirect.form.redirect.source',
                'required' => true,
            ])
            ->add('destination', TextType::class, [
                'label' => 'setono_sylius_redirect.form.redirect.destination',
                'required' => true,
            ])
            ->add('permanent', CheckboxType::class, [
                'label' => 'setono_sylius_redirect.form.redirect.permanent',
                'required' => false,
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'setono_sylius_redirect.form.redirect.enabled',
                'required' => false,
            ])
            ->add('relative', CheckboxType::class, [
                'label' => 'setono_sylius_redirect.form.redirect.relative',
                'required' => false,
            ])
            ->add('redirectFound', CheckboxType::class, [
                'label' => 'setono_sylius_redirect.form.redirect.redirect_found',
                'required' => false,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'setono_sylius_redirect_redirect';
    }
}
