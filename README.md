# Lite image process library
> The current library is based on PHP CURL extension and tested in PHP7.3 or above environment.
> In view of ethical standards, please do not use this library to illegally obtain network resources without permission.

## Install
1. PHP version is greater than or equal to 7.3
2. Extensions must be installed: gd

Please use Composer to install:
```shell script
composer require lfphp/limg
```

## Usage

### 1. CURL method to obtain content
```php
<?php
$img = __DIR__.'/a.jpg';
$new_img = __DIR__.'/b.png';
Limg::fromImg($img)
	->fixOrientate()
	->getInfo($origin_info)
	->resize(800, 400, 'cover')
	->changeFormat('png')
	->addRepeatTextWatermark('hell world')
	->getInfo($target_info)
	->saveAs($new_img);
```
