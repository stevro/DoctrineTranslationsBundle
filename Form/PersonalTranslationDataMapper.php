<?php


namespace Stev\DoctrineTranslationsBundle\Form;


use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Gedmo\Translatable\TranslatableListener;
use Stev\DoctrineTranslationsBundle\Interfaces\TranslatableFieldInterface;
use Psr\Log\LoggerInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class PersonalTranslationDataMapper
 *
 */
class PersonalTranslationDataMapper implements DataMapperInterface
{

    /**
     * @var EntityManager
     */
    private $em;


    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * @var FormBuilderInterface
     */
    private $builder;

    /**
     * @var array
     */
    private $translations = [];

    /**
     * @var array
     */
    private $locales = [];

    /**
     * @var
     */
    private $required_locale;

    /**
     * @var array
     */
    private $property_names = [];


    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * DataMapper constructor.
     *
     * @param ManagerRegistry $registry
     * @param TranslationRepository|null $repository
     */
    public function __construct(ManagerRegistry $registry, Reader $annotationReader, LoggerInterface $logger)
    {

        $this->em = $registry->getManager();
        $this->annotationReader = $annotationReader;
        $this->logger = $logger;

    }

    /**
     * This allows for em override in case you have multiple connections
     * @param EntityManagerInterface $em
     */
    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;

    }


    /**
     * @param FormBuilderInterface $builderInterface
     */
    public function setBuilder(FormBuilderInterface $builderInterface)
    {
        $this->builder = $builderInterface;
    }

    /**
     * @param $locale
     */
    public function setRequiredLocale($locale)
    {
        $this->required_locale = $locale;
    }

    /**
     * @param       $name
     * @param       $type
     * @param array $options
     *
     * @return DataMapper
     * @throws \Exception
     */
    public function add($name, $type, $options = [])
    {

        $this->property_names[] = $name;

        $field = $this->builder
            ->add($name, $type, [
                'label'=> isset($options['label']) ? $options['label'] : null,
                'required'=> isset($options['required']) ? $options['required'] : null,
            ])
            ->get($name);

        if (!$field->getType()
                ->getInnerType() instanceof TranslatableFieldInterface) {
            throw new \Exception("{$name} must implement TranslatableFieldInterface");
        }

        foreach ($this->locales as $iso) {

            $options = [
                "label" => $iso,
                "attr" => isset($options["attr"]) ? $options["attr"] : [],
                "required" => ($iso == $this->required_locale && (!isset($options["required"]) || $options["required"])),
            ];

            $field->add(
                $iso,
                get_class(
                    $field->getType()
                        ->getParent()
                        ->getInnerType()
                ),
                $options
            );

        }

        return $this;

    }

    /**
     * Maps properties of some data to a list of forms.
     *
     * @param mixed $data Structured data.
     * @param FormInterface[] $forms A list of {@link FormInterface} instances.
     *
     * @throws Exception\UnexpectedTypeException if the type of the data parameter is not supported.
     */
    public function mapDataToForms($data, $forms)
    {
        if (!$data) {
            return;
        }

        foreach ($forms as $form) {
            $this->translations = [];

            /** @var ArrayCollection $translations */
            $translations = $this->getTranslations($data);

            $methodName = 'get'.ucfirst($form->getName());
            $defaultData = "";
            if (method_exists($data, $methodName)) {
                $defaultData = $data->{$methodName}();
            }

            if (false !== in_array($form->getName(), $this->property_names)) {
                $values = [];

                foreach ($this->getLocales() as $iso) {

                    /** @var AbstractPersonalTranslation $trans */
                    $trans = $translations->filter(function ($p) use ($iso, $form) {
                        return $p->getLocale() === $iso && $form->getName() === $p->getField();
                    })->first();

                    if ($trans) {
                        $values[$iso] = $trans->getContent() ? $trans->getContent() : '';
                    }

                    if ($iso === $this->required_locale && isset($values[$this->required_locale]) === false && $defaultData !== '') {
                        $values[$this->required_locale] = $defaultData;
                    }
                }

                $form->setData($values);
            } else {
                if (false === $form->getConfig()->getOption("mapped") || null === $form->getConfig()->getOption("mapped")) {
                    continue;
                }
                $accessor = PropertyAccess::createPropertyAccessor();
                $form->setData($accessor->getValue($data, $form->getName()));
                
            }
        }
    }

    /**
     * @param $entity
     *
     * @return array
     */
    public function getTranslations($entity)
    {


        if (!count($this->translations)) {

            $this->translations = $entity->getTranslations();
        }

        return $this->translations;

    }

    /**
     * @return array
     */
    public function getLocales()
    {
        return $this->locales;
    }

    /**
     * @param array $locales
     */
    public function setLocales(array $locales)
    {
        $this->locales = $locales;
    }

    /**
     * Maps the data of a list of forms into the properties of some data.
     *
     * @param FormInterface[] $forms A list of {@link FormInterface} instances.
     * @param mixed $data Structured data.
     *
     * @throws Exception\UnexpectedTypeException if the type of the data parameter is not supported.
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function mapFormsToData($forms, &$data)
    {
        /**
         * @var $form FormInterface
         */
        foreach ($forms as $form) {

            $entityInstance = $data;

            if (false !== in_array($form->getName(), $this->property_names)) {

                $meta = $this->em->getClassMetadata(get_class($entityInstance));
                $listener = new TranslatableListener;
                $listener->loadMetadataForObjectClass($this->em, $meta);
                $config = $listener->getConfiguration($this->em, $meta->name);

                $translations = $form->getData();

                foreach ($this->getLocales() as $iso) {

                    if ($this->required_locale === $iso) {
                        //set the value in the original table
                        $accessor = PropertyAccess::createPropertyAccessor();
                        $accessor->setValue($entityInstance, $form->getName(), $translations[$iso]);
                    }

                    if (isset($config['translationClass'])) {

                        $t = $this->createOrUpdatePersonalTranslation($entityInstance, $config['translationClass'],
                            $iso, $form->getName(), $translations[$iso]);


                    } else {

                        $t = $this->updatePersonalTranslation($entityInstance, $iso, $form->getName(),
                            $translations[$iso]);

                    }

                    if ($t instanceof AbstractPersonalTranslation) {
                        $this->em->persist($t);
                    }
                }

            } else {

                if (false === $form->getConfig()->getOption("mapped") || null === $form->getConfig()->getOption("mapped")) {
                    continue;
                }

                if(true === $form->getConfig()->getOption('disabled')){
                    continue;
                }

                $accessor = PropertyAccess::createPropertyAccessor();
                $accessor->setValue($entityInstance, $form->getName(), $form->getData());

            }

        }

    }

    private function createOrUpdatePersonalTranslation(
        $object,
        $className,
        $locale,
        $field,
        $content
    ) {
        //creates a new Personal Translation

        if (!$object->getTranslations()->isEmpty()) {
            /** @var AbstractPersonalTranslation $existingTrans */
            foreach ($object->getTranslations() as $existingTrans) {
                if ($existingTrans->getLocale() === $locale && $existingTrans->getField() === $field) {

                    if ($content === null) {
                        $object->removeTranslation($existingTrans);

                        return null;
                    }

                    return $this->updatePersonalTranslation($existingTrans, $locale, $field, $content);
                }
            }
        }

        if ($content === null) {
            return null;
        }

        $translation = new $className($locale, $field, $content);

        $object->addTranslation($translation);

        return $translation;
    }

    private function updatePersonalTranslation(
        AbstractPersonalTranslation $translation,
        $locale,
        $field,
        $content
    ) {
        $translation->setLocale($locale);
        $translation->setField($field);
        $translation->setContent($content);

        return $translation;
    }

}