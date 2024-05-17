# Composer Prefer Local Packages

When a package is available in a sibling or sub-directory, use that instead of Packagist or other configured repos.

```
composer config minimum-stability dev
composer config prefer-stable true

composer config allow-plugins.brianhenryie/composer-prefer-local
composer require --dev brianhenryie/composer-prefer-local
```
