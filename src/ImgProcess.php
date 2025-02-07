<?php
namespace LFPhp\Limg;

use Intervention\Image\ImageManager;
use Intervention\Image\ImageManagerStatic;
use function LFPhp\Func\create_tmp_file;
use function LFPhp\Func\get_mimes_by_extension;

class ImgProcess {
	private $origin_file;
	private $img;

	const RESIZE_TYPE_CONTAIN = 'contain';
	const RESIZE_TYPE_COVER = 'cover';
	const RESIZE_TYPE_SCALE_DOWN = 'scale-down';

	public function __construct($img_file){
		$this->origin_file = $img_file;
		$this->img = ImageManagerStatic::make($img_file);
	}

	public static function fromImg($img_file){
		return new self($img_file);
	}

	/**
	 * 生成缩略图
	 * @param int $width
	 * @param int $height
	 * @param string $resize_type 缩放类型
	 * @param string $format 格式
	 * @return $this
	 */
	public function thumb($width, $height, $resize_type = self::RESIZE_TYPE_COVER, $format = 'jpg'){
		$this->fixOrientate()->changeFormat($format)->resize($width, $height, $resize_type);
		return $this;
	}

	/**
	 * @return $this
	 */
	public function fixOrientate(){
		$this->img->orientate();
		return $this;
	}

	public function save($quality = 90){
		$this->img->save($this->origin_file, $quality);
		return $this;
	}

	public function saveAs($new_file, $quality = 90){
		$this->img->save($new_file, $quality);
		$this->origin_file = $new_file;
		return $this;
	}

	public function getInfo(&$img_info){
		$img_info = [
			'width'  => $this->img->width(),
			'height' => $this->img->height(),
			'mime'   => $this->img->mime(),
		];
		return $this;
	}

	public function resize($width, $height, $resize_type){
		$img_w = $this->img->width();
		$img_h = $this->img->height();
		$small = $img_w <= $width && $img_h <= $height;
		$zoom_in_small_image = true;

		//进行缩放操作
		if(!$small || $zoom_in_small_image){
			//长边压缩
			if($resize_type == 'contain'){
				$ratio = min($width/$img_w, $height/$img_h);
				$new_w = $img_w*$ratio;
				$new_h = $img_h*$ratio;
				$canvas_w = $new_w;
				$canvas_h = $new_h;
			}//短边压缩
			else{
				$ratio = max($width/$img_w, $height/$img_h);
				$new_w = $img_w*$ratio;
				$new_h = $img_h*$ratio;
				$canvas_w = $width;
				$canvas_h = $height;
			}
			$this->img->resize($new_w, $new_h);
			$canvas = ImageManagerStatic::canvas($canvas_w, $canvas_h);
		}//不缩放
		else{
			$canvas = ImageManagerStatic::canvas($img_w, $img_h);
		}
		$canvas->insert($this->img, 'center');
		$tmp_name = create_tmp_file('', '', '', 0777, true);
		$canvas->save($tmp_name);
		$this->img = ImageManagerStatic::make($tmp_name);
		return $this;
	}

	public function addImgWatermark($pattern, array $option = []){
		return $this;
	}

	public function addTextWatermark($text, array $option = []){
		return $this;
	}

	/**
	 * 添加重复文字水印
	 * @param $text
	 * @param array $option
	 * @return $this
	 */
	public function addRepeatTextWatermark($text, array $option = []){
		$option = array_merge([
			'font-file'  => dirname(__DIR__).'/assets/fz.ttf', //字体文件
			'font-size'  => 36, //字号
			'font-color' => [255, 255, 255, 0.2], //字体颜色
			'rotate'     => 30, //旋转角度
			'gap'        => [100, 20], //间距(水印文字水平间距、垂直间距)
		], $option);
		$font_file = $option['font-file'];
		$manager = new ImageManager();

		$width = $this->img->width();
		$height = $this->img->height();

		//水印文字长宽测量
		$tmp_img = $manager->canvas(1, 1);
		$box = null;
		$tmp_img->text($text, 0, 0, function($font) use ($option, $font_file, &$box){
			$font->file($font_file);
			$font->size($option['font-size']);
			$box = $font->getBoxSize();
		});
		$text_width = $box['width'];
		$text_height = $box['height'];

		$x_interval = $text_width + $option['gap'][0];
		$y_interval = $text_height + $option['gap'][1];

		$canvas_size = max($width, $height)*2;
		$watermark = $manager->canvas($canvas_size, $canvas_size);
		$row = 0;
		for($y = 0; $y < $canvas_size; $y += $y_interval){
			$row++;
			$indent = ($row%2)*$text_width/2;
			for($x = $indent; $x < $canvas_size; $x += $x_interval){
				$watermark->text($text, $x, $y, function($font) use ($option, $font_file){
					$font->file($font_file);
					$font->size($option['font-size']);
					$font->color($option['font-color']); // 白色，透明度 50%
					$font->align('center');
					$font->valign('center');
				});
			}
		}

		// 旋转水印
		$watermark->rotate($option['rotate']);
		$watermark = $watermark->crop($width, $height, intval(($canvas_size - $width)/2), intval(($canvas_size - $height)/2));

		// 将水印应用到原始图像
		$this->img->insert($watermark, 'top-left', intval(-0.25*$width), intval(-0.25*$height));
		return $this;
	}

	/**
	 * 更换图片格式
	 * @param string[]|string $toFormat 需要更换的图片格式，如果是列表，采用第一个作为目标格式。如果原图格式与目标格式相同，则不转换
	 */
	public function changeFormat($toFormat, $quality = 90){
		$info = [];
		$this->getInfo($info);
		$toFormat = is_string($toFormat) ? [$toFormat] : $toFormat;
		$to_mimes = [];
		foreach($toFormat as $tf){
			$ms = get_mimes_by_extension($tf);
			$to_mimes = array_merge($to_mimes, $ms);
		}
		if(in_array($info['mime'], $to_mimes)){
			return $this;
		}
		$this->img->encode($toFormat[0], $quality);
		return $this;
	}
}
