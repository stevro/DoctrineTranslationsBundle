<?php

namespace Stev\DoctrineTranslationsBundle\Twig;


use Symfony\Component\Intl\Intl;
use Twig\TwigFilter;

class TwigExtension extends \Twig_Extension
{


    public function getFilters()
    {
        return [
            new TwigFilter('language', [$this, 'formatLanguage']),
        ];
    }

    public function getFunctions()
    {
        return [

        ];
    }

    public function getTests()
    {
        return [

        ];
    }

    public function formatLanguage($locale)
    {
        return Intl::getLanguageBundle()->getName($locale);
    }


}
