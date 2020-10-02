# doctrine-translations-bundle
Symfony 2 Bundle providing form data mappers to handle translations using doctrine entities


# Install
Run: composer require stev/doctrine-translations-bundle


# Inspired from
https://github.com/Simettric/DoctrineTranslatableFormBundle

# WARNING
This works only if the user current locale is the same as the default locale in your settings (stof_doctrine_extensions -> default_locale), otherwise, gedmo listener will override your translations.
So if your app has a feature allowing the user to switch his language, this bundle won't work for you.
This works for admin panels where admins work only in one language and whish to edit the content in multiple languages at the same time. And then the content is displayed on another application where users can switch languages.
