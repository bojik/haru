<?php
class Miao_Form_KCaptcha
{

	/**
	 * # do not change without changing font files!
	 * @var string
	 */
	private $_alphabet = "0123456789abcdefghijklmnopqrstuvwxyz";

	/**
	 * #alphabet without similar symbols (o=0, 1=l, i=j, t=f)
	 * @var string
	 */
	private $_allowedSymbols = "23456789abcdegikpqsvxyz";

	/**
	 * folder with fonts
	 * @var string
	 */
	private $_fontsdir;

	/**
	 * CAPTCHA string length
	 * @var number
	 */
	private $_length;

	/**
	 * CAPTCHA width image size (you do not need to change it, this parameters is optimal)
	 * @var number
	 */
	private $_width = 160;

	/**
	 * CAPTCHA height image size (you do not need to change it, this parameters is optimal)
	 * @var number
	 */
	private $_height = 80;

	/**
	 *  symbol's vertical fluctuation amplitude
	 * @var number
	 */
	private $_fluctuationAmplitude = 8;

	/**
	 * white noise. 0 is no white noise
	 * @var unknown_type
	 */
	private $_whiteNoiseDensity;

	/**
	 * black noise. 0 is no black noise
	 * @var unknown_type
	 */
	private $_blackNoiseDensity;

	/**
	 * increase safety by prevention of spaces between symbols
	 */
	private $_noSpaces = true;

	/**
	 * set to false to remove credits line. Credits adds 12 pixels to image height
	 * @var bool
	 */
	private $_showCredits = false;

	/**
	 * if empty, HTTP_HOST will be shown
	 * @var unknown_type
	 */
	private $_credits;

	/**
	 * CAPTCHA foreground image colors (RGB, 0-255)
	 * @var unknown_type
	 */
	private $_foregroundColor;

	/**
	 * CAPTCHA background image colors (RGB, 0-255)
	 * @var unknown_type
	 */
	private $_backgroundColor;

	/**
	 * JPEG quality of CAPTCHA image (bigger is better quality, but larger file size)
	 */
	private $_jpegQuality = 90;

	private $_fonts = array();

	private $_keystring;

	public function __construct( array $config = array() )
	{
		$this->_initDefault();

		if ( empty( $config ) )
		{
			$config = Miao_Config::Libs( __CLASS__ );
			$config = $config->toArray();
		}

		if ( !empty( $config ) )
		{
			foreach ( $config as $property => $value )
			{
				if ( property_exists( $this, $property ) )
				{
					//TODO: needs setter function and check
					$this->$property = $value;
				}
			}
		}

		$this->_initFonts();
	}

	/**
	 *
	 * @return string
	 */
	public function getKeyString()
	{
		return $this->_keystring;
	}

	public function generate()
	{
		$this->_generate();
	}

	protected function _initCredits()
	{
		//TODO: replace host
		$this->_credits = empty( $this->_credits ) ? $_SERVER[ 'HTTP_HOST' ] : $this->_credits;
	}

	protected function _initFonts()
	{
		$this->_fonts = array();
		$fontsdirAbsolute = $this->_fontsdir;
		$handle = opendir( $fontsdirAbsolute );
		if ( $handle )
		{
			while ( false !== ( $file = readdir( $handle ) ) )
			{
				if ( 0 === strripos( strrev( $file ), 'gnp.' ) )
				{
					$this->_fonts[] = $fontsdirAbsolute . '/' . $file;
				}
			}
			closedir( $handle );
		}
	}

	protected function _initDefault()
	{
		$this->_alphabet = "0123456789abcdefghijklmnopqrstuvwxyz";
		$this->_allowed_symbols = "23456789abcdegikpqsvxyz";

		$this->_fontsdir = Miao_Path::getDefaultInstance()->getModuleRoot( __CLASS__ ) . '/data/fonts';
		$this->_length = mt_rand( 5, 7 );

		$this->_width = 160;
		$this->_height = 80;

		$this->_fluctuationAmplitude = 8;

		$this->_whiteNoiseDensity = 1 / 6;
		$this->_blackNoiseDensity = 1 / 30;

		$this->_noSpaces = true;
		$this->_showCredits = false;
		$this->_credits = '';

		$this->_foregroundColor = array(
			mt_rand( 0, 80 ),
			mt_rand( 0, 80 ),
			mt_rand( 0, 80 ) );
		$this->_backgroundColor = array(
			mt_rand( 220, 255 ),
			mt_rand( 220, 255 ),
			mt_rand( 220, 255 ) );

		$this->_jpegQuality = 90;
	}

	protected function _generate()
	{
		$fonts = $this->_fonts;
		$alphabet = $this->_alphabet;
		$allowed_symbols = $this->_allowedSymbols;
		$length = $this->_length;
		$width = $this->_width;
		$height = $this->_height;
		$fluctuation_amplitude = $this->_fluctuationAmplitude;
		$white_noise_density = $this->_whiteNoiseDensity;
		$black_noise_density = $this->_blackNoiseDensity;
		$no_spaces = $this->_noSpaces;
		$show_credits = $this->_showCredits;
		$credits = $this->_credits;
		$foreground_color = $this->_foregroundColor;
		$background_color = $this->_backgroundColor;
		$jpeg_quality = $this->_jpegQuality;
		$alphabet_length = strlen( $alphabet );

		do
		{
			// generating random keystring
			while ( true )
			{
				$this->_keystring = '';
				for( $i = 0; $i < $length; $i++ )
				{
					$this->_keystring .= $allowed_symbols{ mt_rand( 0, strlen( $allowed_symbols ) - 1 ) };
				}
				if ( !preg_match( '/cp|cb|ck|c6|c9|rn|rm|mm|co|do|cl|db|qp|qb|dp|ww/', $this->_keystring ) )
					break;
			}

			$font_file = $fonts[ mt_rand( 0, count( $fonts ) - 1 ) ];
			$font = imagecreatefrompng( $font_file );
			imagealphablending( $font, true );

			$fontfile_width = imagesx( $font );
			$fontfile_height = imagesy( $font ) - 1;

			$font_metrics = array();
			$symbol = 0;
			$reading_symbol = false;

			// loading font
			for( $i = 0; $i < $fontfile_width && $symbol < $alphabet_length; $i++ )
			{
				$transparent = ( imagecolorat( $font, $i, 0 ) >> 24 ) == 127;

				if ( !$reading_symbol && !$transparent )
				{
					$font_metrics[ $alphabet{ $symbol } ] = array(
						'start' => $i );
					$reading_symbol = true;
					continue;
				}

				if ( $reading_symbol && $transparent )
				{
					$font_metrics[ $alphabet{ $symbol } ][ 'end' ] = $i;
					$reading_symbol = false;
					$symbol++;
					continue;
				}
			}

			$img = imagecreatetruecolor( $width, $height );
			imagealphablending( $img, true );
			$white = imagecolorallocate( $img, 255, 255, 255 );
			$black = imagecolorallocate( $img, 0, 0, 0 );

			imagefilledrectangle( $img, 0, 0, $width - 1, $height - 1, $white );

			// draw text
			$x = 1;
			$odd = mt_rand( 0, 1 );
			if ( $odd == 0 )
				$odd = -1;
			for( $i = 0; $i < $length; $i++ )
			{
				$m = $font_metrics[ $this->_keystring{ $i } ];

				$y = ( ( $i % 2 ) * $fluctuation_amplitude - $fluctuation_amplitude / 2 ) * $odd + mt_rand( -round( $fluctuation_amplitude / 3 ), round( $fluctuation_amplitude / 3 ) ) + ( $height - $fontfile_height ) / 2;

				if ( $no_spaces )
				{
					$shift = 0;
					if ( $i > 0 )
					{
						$shift = 10000;
						for( $sy = 3; $sy < $fontfile_height - 10; $sy += 1 )
						{
							for( $sx = $m[ 'start' ] - 1; $sx < $m[ 'end' ]; $sx += 1 )
							{
								$rgb = imagecolorat( $font, $sx, $sy );
								$opacity = $rgb >> 24;
								if ( $opacity < 127 )
								{
									$left = $sx - $m[ 'start' ] + $x;
									$py = $sy + $y;
									if ( $py > $height )
										break;
									for( $px = min( $left, $width - 1 ); $px > $left - 200 && $px >= 0; $px -= 1 )
									{
										$color = imagecolorat( $img, $px, $py ) & 0xff;
										if ( $color + $opacity < 170 )
										{ // 170 - threshold
											if ( $shift > $left - $px )
											{
												$shift = $left - $px;
											}
											break;
										}
									}
									break;
								}
							}
						}
						if ( $shift == 10000 )
						{
							$shift = mt_rand( 4, 6 );
						}
					}
				}
				else
				{
					$shift = 1;
				}
				imagecopy( $img, $font, $x - $shift, $y, $m[ 'start' ], 1, $m[ 'end' ] - $m[ 'start' ], $fontfile_height );
				$x += $m[ 'end' ] - $m[ 'start' ] - $shift;
			}
		} while ( $x >= $width - 10 ); // while not fit in canvas


		//noise
		$white = imagecolorallocate( $font, 255, 255, 255 );
		$black = imagecolorallocate( $font, 0, 0, 0 );
		for( $i = 0; $i < ( ( $height - 30 ) * $x ) * $white_noise_density; $i++ )
		{
			imagesetpixel( $img, mt_rand( 0, $x - 1 ), mt_rand( 10, $height - 15 ), $white );
		}
		for( $i = 0; $i < ( ( $height - 30 ) * $x ) * $black_noise_density; $i++ )
		{
			imagesetpixel( $img, mt_rand( 0, $x - 1 ), mt_rand( 10, $height - 15 ), $black );
		}

		$center = $x / 2;

		// credits. To remove, see configuration file
		$img2 = imagecreatetruecolor( $width, $height + ( $show_credits ? 12 : 0 ) );
		$foreground = imagecolorallocate( $img2, $foreground_color[ 0 ], $foreground_color[ 1 ], $foreground_color[ 2 ] );
		$background = imagecolorallocate( $img2, $background_color[ 0 ], $background_color[ 1 ], $background_color[ 2 ] );
		imagefilledrectangle( $img2, 0, 0, $width - 1, $height - 1, $background );
		imagefilledrectangle( $img2, 0, $height, $width - 1, $height + 12, $foreground );
		$credits = empty( $credits ) ? $_SERVER[ 'HTTP_HOST' ] : $credits;
		imagestring( $img2, 2, $width / 2 - imagefontwidth( 2 ) * strlen( $credits ) / 2, $height - 2, $credits, $background );

		// periods
		$rand1 = mt_rand( 750000, 1200000 ) / 10000000;
		$rand2 = mt_rand( 750000, 1200000 ) / 10000000;
		$rand3 = mt_rand( 750000, 1200000 ) / 10000000;
		$rand4 = mt_rand( 750000, 1200000 ) / 10000000;
		// phases
		$rand5 = mt_rand( 0, 31415926 ) / 10000000;
		$rand6 = mt_rand( 0, 31415926 ) / 10000000;
		$rand7 = mt_rand( 0, 31415926 ) / 10000000;
		$rand8 = mt_rand( 0, 31415926 ) / 10000000;
		// amplitudes
		$rand9 = mt_rand( 330, 420 ) / 110;
		$rand10 = mt_rand( 330, 450 ) / 100;

		//wave distortion


		for( $x = 0; $x < $width; $x++ )
		{
			for( $y = 0; $y < $height; $y++ )
			{
				$sx = $x + ( sin( $x * $rand1 + $rand5 ) + sin( $y * $rand3 + $rand6 ) ) * $rand9 - $width / 2 + $center + 1;
				$sy = $y + ( sin( $x * $rand2 + $rand7 ) + sin( $y * $rand4 + $rand8 ) ) * $rand10;

				if ( $sx < 0 || $sy < 0 || $sx >= $width - 1 || $sy >= $height - 1 )
				{
					continue;
				}
				else
				{
					$color = imagecolorat( $img, $sx, $sy ) & 0xFF;
					$color_x = imagecolorat( $img, $sx + 1, $sy ) & 0xFF;
					$color_y = imagecolorat( $img, $sx, $sy + 1 ) & 0xFF;
					$color_xy = imagecolorat( $img, $sx + 1, $sy + 1 ) & 0xFF;
				}

				if ( $color == 255 && $color_x == 255 && $color_y == 255 && $color_xy == 255 )
				{
					continue;
				}
				else if ( $color == 0 && $color_x == 0 && $color_y == 0 && $color_xy == 0 )
				{
					$newred = $foreground_color[ 0 ];
					$newgreen = $foreground_color[ 1 ];
					$newblue = $foreground_color[ 2 ];
				}
				else
				{
					$frsx = $sx - floor( $sx );
					$frsy = $sy - floor( $sy );
					$frsx1 = 1 - $frsx;
					$frsy1 = 1 - $frsy;

					$newcolor = ( $color * $frsx1 * $frsy1 + $color_x * $frsx * $frsy1 + $color_y * $frsx1 * $frsy + $color_xy * $frsx * $frsy );

					if ( $newcolor > 255 )
						$newcolor = 255;
					$newcolor = $newcolor / 255;
					$newcolor0 = 1 - $newcolor;

					$newred = $newcolor0 * $foreground_color[ 0 ] + $newcolor * $background_color[ 0 ];
					$newgreen = $newcolor0 * $foreground_color[ 1 ] + $newcolor * $background_color[ 1 ];
					$newblue = $newcolor0 * $foreground_color[ 2 ] + $newcolor * $background_color[ 2 ];
				}

				imagesetpixel( $img2, $x, $y, imagecolorallocate( $img2, $newred, $newgreen, $newblue ) );
			}
		}

		header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Cache-Control: post-check=0, pre-check=0', FALSE );
		header( 'Pragma: no-cache' );
		if ( function_exists( "imagejpeg" ) )
		{
			header( "Content-Type: image/jpeg" );
			imagejpeg( $img2, null, $jpeg_quality );
		}
		else if ( function_exists( "imagegif" ) )
		{
			header( "Content-Type: image/gif" );
			imagegif( $img2 );
		}
		else if ( function_exists( "imagepng" ) )
		{
			header( "Content-Type: image/x-png" );
			imagepng( $img2 );
		}
	}
}