<?php


namespace Stev\DoctrineTranslationsBundle\Form;


use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

/**
 *
 * stof_doctrine_extensions:
 * default_locale: %locale%
 * translation_fallback: true
 * persist_default_translation: true
 * orm:
 * default:
 * translatable: true
 *
 * Class AbstractType
 */
abstract class AbstractTranslatableType extends AbstractType
{

    /**
     * @var array
     */
    private $locales = [];

    /**
     * @var
     */
    private $required_locale;

    /**
     * @var DataMapperInterface
     */
    private $mapper;

    /**
     * AbstractTranslatableType constructor.
     *
     * @param DataMapperInterface $dataMapper
     */
    function __construct(DataMapperInterface $dataMapper)
    {
        $this->mapper = $dataMapper;
    }

    /**
     * @param $iso
     */
    public function setRequiredLocale($iso)
    {
        $this->required_locale = $iso;
    }

    /**
     * @param array $locales
     */
    public function setLocales(array $locales)
    {
        $this->locales = $locales;
    }

    /**
     * @param FormBuilderInterface $builderInterface
     * @param array $options
     *
     * @return DataMapperInterface
     */
    protected function createTranslatableMapper(FormBuilderInterface $builderInterface, array $options)
    {

        $this->mapper->setBuilder($builderInterface, $options);
        $this->mapper->setLocales($options["locales"]);
        $this->mapper->setRequiredLocale($options["required_locale"]);
        $builderInterface->setDataMapper($this->mapper);

        return $this->mapper;
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureTranslationOptions(OptionsResolver $resolver)
    {

        $resolver->setRequired(["locales", "required_locale"]);

        $data = [
            'locales' => $this->locales ?: ["en"],
            "required_locale" => $this->required_locale ?: "en",
        ];

        $resolver->setDefaults($data);
    }

}