<?php


namespace Stev\DoctrineTranslationsBundle\Form;


use Stev\DoctrineTranslationsBundle\Interfaces\TranslatableFieldInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TranslatableTextType
 *

 */
class TranslatableTextType extends AbstractType implements TranslatableFieldInterface
{

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                "compound" => true,
            ]
        );
        $resolver->setRequired(["compound"]);
        $resolver->setAllowedValues("compound", true);

    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }

    public function getBlockPrefix()
    {
        return 'translatable_text';
    }
}