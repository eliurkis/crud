# Laravel CRUD Generator

[![Latest Version](https://img.shields.io/github/release/eliurkis/crud.svg?style=flat-square)](https://github.com/eliurkis/crud/releases)
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![SensioLabs Insight](https://img.shields.io/sensiolabs/i/6a085926-22ff-4c51-98f1-98044c411abe.svg?style=flat-square)](https://insight.sensiolabs.com/projects/6a085926-22ff-4c51-98f1-98044c411abe)
[![Quality Score][ico-code-quality]][link-code-quality]
[![StyleCI](https://styleci.io/repos/77415299/shield?branch=master)](https://styleci.io/repos/77415299)
[![Total Downloads][ico-downloads]][link-downloads]

A Laravel CRUD Generator.

## Install

Require this package with composer using the following command:

``` bash
$ composer require eliurkis/crud
```

After updating composer, add the service provider to the `providers` array in `config/app.php`

```php
Eliurkis\Crud\CrudServiceProvider::class,
```

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email me@eliurkis.com instead of using the issue tracker.

## Credits

- [Eliurkis Diaz][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/eliurkis/crud.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/eliurkis/crud/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/eliurkis/crud.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/eliurkis/crud.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/eliurkis/crud.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/eliurkis/crud
[link-travis]: https://travis-ci.org/eliurkis/crud
[link-scrutinizer]: https://scrutinizer-ci.com/g/eliurkis/crud/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/eliurkis/crud
[link-downloads]: https://packagist.org/packages/eliurkis/crud
[link-author]: https://github.com/eliurkis
[link-contributors]: ../../contributors
