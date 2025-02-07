<?php

use LFPhp\Limg\ImgProcess;
use function LFPhp\Func\dump;

include dirname(__DIR__).'/vendor/autoload.php';

$img = __DIR__.'/a.jpg';
$new_img = __DIR__.'/b.png';
ImgProcess::fromImg($img)
	->fixOrientate()
	->resize(800, 400, ImgProcess::RESIZE_TYPE_CONTAIN)
	->changeFormat('png')
	->getInfo($info)
	->addRepeatTextWatermark('hell world')
	->saveAs($new_img);

dump($info, 1);
