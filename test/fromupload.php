<?php

use LFPhp\Limg\Limg;
use function LFPhp\Func\dump;

include dirname(__DIR__).'/vendor/autoload.php';

$img = __DIR__.'/a.jpg';
$new_img = __DIR__.'/b.png';
Limg::fromImg($img)
	->fixOrientate()
	->getInfo($origin_info)
	->resize(800, 400, 'cover', '#000000')
	->changeFormat('png')
	->addRepeatTextWatermark('hell world')
	->getInfo($target_info)
	->saveAs($new_img);

dump($origin_info, $target_info, 1);
