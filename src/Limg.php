<?php
namespace LFPhp\Limg;

use Intervention\Image\ImageManager;
use Intervention\Image\ImageManagerStatic;
use function LFPhp\Func\create_tmp_file;
use function LFPhp\Func\get_mimes_by_extension;
use function LFPhp\Func\html_object_fit_calculate;

class Limg {
	private $origin_file;
	private $img;

	const RELATE_POSITION_TOP_LEFT = 'top-left';
	const RELATE_POSITION_TOP_RIGHT = 'top-right';
	const RELATE_POSITION_TOP_CENTER = 'top-center';
	const RELATE_POSITION_LEFT_CENTER = 'left-center';
	const RELATE_POSITION_RIGHT_CENTER = 'right-center';
	const RELATE_POSITION_BOTTOM_LEFT = 'bottom-left';
	const RELATE_POSITION_BOTTOM_RIGHT = 'bottom-right';
	const RELATE_POSITION_BOTTOM_CENTER = 'bottom-center';

	private function __construct($img_file){
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
	 * @param string $resize_type 缩放类型，请参考：https://developer.mozilla.org/zh-CN/docs/Web/CSS/object-fit
	 * @param string $format 格式
	 * @return $this
	 */
	public function thumb($width, $height, $resize_type = 'cover', $format = 'jpg'){
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

	/**
	 * 获取当前结果信息
	 * @param array $img_info
	 * @return $this
	 */
	public function getInfo(&$img_info){
		$img_info = [
			'width'  => $this->img->width(),
			'height' => $this->img->height(),
			'mime'   => $this->img->mime(),
		];
		return $this;
	}

	/**
	 * 缩放图片
	 * @param int $width 目标宽度
	 * @param int $height 目标高度
	 * @param string $resize_type 缩放类型，请参考 https://developer.mozilla.org/zh-CN/docs/Web/CSS/object-fit
	 * @param string $background_color 背景色（如果缩小图片出现空白情况下填充背景色）
	 * @return $this
	 * @throws \Exception
	 */
	public function resize($width, $height, $resize_type, $background_color = '#ffffff'){
		$img_w = $this->img->width();
		$img_h = $this->img->height();

		$result_layout_info = html_object_fit_calculate([$width, $height], [$img_w, $img_h], $resize_type);
		//无需改变情况
		if($result_layout_info['top'] == 0 &&
			$result_layout_info['left'] == 0 &&
			$result_layout_info['width'] == $img_w &&
			$result_layout_info['height'] == $img_h
		){
			return $this;
		}

		$canvas = ImageManagerStatic::canvas($width, $height, $background_color);
		$this->img->resize($result_layout_info['width'], $result_layout_info['height']);
		$canvas->insert($this->img, 'top-left', $result_layout_info['left'], $result_layout_info['top']);
		$tmp_name = create_tmp_file('', '', '', 0777, true);
		$canvas->save($tmp_name);
		$this->img = ImageManagerStatic::make($tmp_name);
		return $this;
	}

	/**
	 * 添加图片水印
	 * @todo
	 * @param string $img_pattern
	 * @param array $option
	 * @return $this
	 */
	public function addImgWatermark($img_pattern, array $option = []){
		$option = array_merge([
			'opacity'                => 1, //透明度：0-1， 0表示透明，1表示不透明
			'rotate'                 => 30, //旋转角度
			'relate_position'        => self::RELATE_POSITION_BOTTOM_RIGHT, //相对位置
			'relate_position_offset' => [0, 0] //相对位置x，y偏移
		], $option);
		return $this;
	}

	/**
	 * 添加重复图片水印
	 * @param string $img_pattern
	 * @param array $option
	 * @return $this
	 */
	public function addRepeatImgWatermark($img_pattern, array $option = []){
		$option = array_merge([
			'opacity' => 1, //透明度：0-1， 0表示透明，1表示不透明
			'rotate'  => 30, //旋转角度
			'gap'     => [0, 0],//间距(横向间距、垂直间距)
		], $option);
		return $this;
	}

	/**
	 * 添加文字水印
	 * @todo
	 * @param string $text
	 * @param array $option
	 * @return $this
	 */
	public function addTextWatermark($text, array $option = []){
		$option = array_merge([
			'font-file'              => dirname(__DIR__).'/assets/fz.ttf', //字体文件
			'font-size'              => 36, //字号
			'font-color'             => [255, 255, 255, 0.2], //字体颜色
			'rotate'                 => 30, //旋转角度
			'relate_position'        => self::RELATE_POSITION_BOTTOM_RIGHT, //相对位置
			'relate_position_offset' => [0, 0] //相对位置x，y偏移
		], $option);
		return $this;
	}

	/**
	 * 文字长宽测量
	 * @param string $text
	 * @param int $font_size 文字字号
	 * @param string $font_file 字体文件
	 * @return array [width, height]
	 */
	public static function getTextSize($text, $font_size, $font_file){
		$manager = new ImageManager();
		$tmp_img = $manager->canvas(1, 1);
		$box = null;
		$tmp_img->text($text, 0, 0, function($font) use ($font_size, $font_file, &$box){
			$font->file($font_file);
			$font->size($font_size);
			$box = $font->getBoxSize();
		});
		$tmp_img->destroy();
		unset($manager);
		return [$box['width'], $box['height']];
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
			'gap'        => [100, 20], //间距(横向间距、垂直间距)
		], $option);
		$font_file = $option['font-file'];
		$manager = new ImageManager();

		$width = $this->img->width();
		$height = $this->img->height();

		[$text_width, $text_height] = self::getTextSize($text, $option['font-size'], $option['font-file']);
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
	 * 需要更换的图片格式，如果是列表，采用第一个作为目标格式。如果原图格式与目标格式相同，则不转换，
	 * 如 toFormat=['jpg', 'png'], 如果原图格式为 png 则不变更，如果原图格式为 bmp 则变更为jpg
	 * @param string[]|string $toFormat 目标格式列表
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
