<?php


namespace Stev\DoctrineTranslationsBundle\Form;


use Stev\DoctrineTranslationsBundle\Interfaces\TranslatableFieldInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TranslatableTextareaType
 *
 */
class TranslatableTextareaType extends AbstractType implements TranslatableFieldInterface
{

    /**
     * @param OptionsResolver $resolver
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
     * @return string
     */
    public function getParent()
    {
        return TextareaType::class;
    }

    public function getBlockPrefix()
    {
        return 'translatable_textarea';
    }

}