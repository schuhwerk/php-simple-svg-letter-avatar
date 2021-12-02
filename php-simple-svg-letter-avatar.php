<?php
/**
 * Class Svg_Avatar
 *
 * @package Php_Simple_Svg_Letter_Avatar
 */
class Svg_Avatar {

	/**
	 * Settings for the Avatar SVG.
	 *
	 * @var (string[]|int|string)[]
	 */
	private $args = array(); // get them with get_arg().

	/**
	 * Constructor.
	 *
	 * @param array $args Pass settings for class (and get_svg).
	 */
	public function __construct( $args = array() ) {
		$defaults   = array(
			'letters_fallback'      => array( '🤷‍♂️', '🤷‍♀️' ), // Default values.
			'cache_days'            => 356, // cache for 356 days.
			'background_randomness' => 'name',
			'palette'               => array(
				'#e27068',
				'#f5b622',
				'#2dad6e',
				'#0e753b',
				'#0091e1',
				'#3748ac',
				'#6e7bc4',
				'#8321a0',
			),
			'initials_count'        => 2, // Jane Margret Doe => JD. 3 => JMD.
		);
		$args       = array_merge( $defaults, $args );
		$this->args = $args;
	}

	/**
	 * Serve the letter avatar as a cacheable svg image.
	 *
	 * @return void
	 */
	public function serve() {
		$name = isset( $_GET['name'] ) && ! empty( $_GET['name'] )
			? strip_tags( stripslashes( $_GET['name'] ) )
			: self::get_random( $this->get_arg( 'letters_fallback' ) );

		self::do_svg_header( $this->get_arg( 'cache_days' ) );

		$bg_random     = $this->get_arg( 'background_randomness' );
		$bg_color_seed = ! empty( $bg_random ) && isset( $_GET[ $bg_random ] )
			? crc32( stripslashes( $_GET[ $bg_random ] ) ) // We use the specified key as a seed for the background color.
			: -1; // -1 means no seed for randomness.

		$svg_opts = array_merge(
			$this->args,
			array(
				'letters'          => strtoupper( self::get_initials( $name, $this->args['initials_count'] ) ),
				'background_color' => self::get_random( $this->get_arg( 'palette' ), $bg_color_seed ),
			)
		);
		echo self::get_svg( $svg_opts );
	}

	/**
	 * Get a key from the args array.
	 *
	 * @param string $key The key to get.
	 * @return mixed The value of the key.
	 */
	public function get_arg( string $key ) {
		return $this->args[ $key ];
	}

	/**
	 * Create the markup for the letter-avatar-svg.
	 *
	 * @param array $args Settings for the SVG.
	 * @return string Markup.
	 */
	public static function get_svg( array $args = array() ) {
		$defaults = array(
			'font_family'      => 'Roboto, Arial, Helvetica, sans-serif',
			'letters'          => '?',
			'background_color' => '#fff',
			'text_color'       => '', // if empty: automatically set to contrast with background.
		);
		$args     = array_merge( $defaults, $args );

		$is_light = self::is_color_light( $args['background_color'] );

		$text_color    = $is_light ? '#000' : '#fff';
		$overlay_color = $is_light ? '#fff' : '#000'; // opposite of text, better readability.
		$shadow_int    = $is_light ? 255 : 0;  // opposite of text, better readability.
		$blend_mode    = 'hard-light';

		$text_color = empty( $args['text_color'] ) ? $text_color : $args['text_color'];

		return "
			<svg class='s-avatar' version='1.1' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'>
				<style type='text/css'>
					.s-avatar-blend { mix-blend-mode: $blend_mode; }
					.s-avatar-multiply { mix-blend-mode: 'multiply'; }
					.s-avatar-shadow { filter: drop-shadow( 1px 0px 2px rgba($shadow_int, $shadow_int, $shadow_int, .3)); }
					.s-avatar rect { width: 100%; height: 100%; }
				</style>

				<linearGradient id='s-avatar-gradient' gradientUnits='userSpaceOnUse' x1='0' y1='0' x2='40' y2='40'>
					<stop offset='0' style='stop-color: $overlay_color; stop-opacity: 0' />
					<stop offset='1' style='stop-color: $overlay_color; stop-opacity: 0.35' />
				</linearGradient>

				<filter id='s-avatar-noise' width='40'>
					<feTurbulence 
						x='0' 
						y='0'
						height='100%'
						width='100%'
						type='turbulence' 
						baseFrequency='2' 
						numOctaves='30' 
						stitchTiles='stitch'
					>
					</feTurbulence>
					<feComponentTransfer>
						<feFuncA type='linear' slope='0.2'/>
					</feComponentTransfer>
				</filter>

				<rect class='s-avatar-rect' fill='url(#s-avatar-gradient)'></rect>
				<rect class='s-avatar-rect s-avatar-multiply' filter='url(#s-avatar-noise)'></rect>
				<rect class='s-avatar-rect s-avatar-blend' fill='{$args['background_color']}' ></rect>
				
				<text 
					fill='$text_color' 
					class='s-avatar-shadow'
					x='20' y='28' 
					text-anchor='middle' 
					font-family='{$args['font_family']}'
					font-size='1.3em'
				>
					{$args['letters']}
				</text>
			</svg>";
	}

	/**
	 * Output the header for the svg.
	 *
	 * @param int $cache_days How many days should the image be cached in the user's browser.
	 * @return void
	 */
	private static function do_svg_header( int $cache_days ) {
		header( 'Content-type: image/svg+xml' );
		header( 'Cache-Control: private, max-age=10800, pre-check=10800' );
		header( 'Pragma: private' );
		header( 'Expires: ' . gmdate( DATE_RFC822, strtotime( $cache_days . 'day' ) ) );
		// careful, with newlines here. Don't add line-breaks.
		echo "<?xml version='1.0' standalone='no'?>
			<!DOCTYPE svg PUBLIC '-//W3C//DTD SVG 1.1//EN' 'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd'>";
	}

	/**
	 * Get a random value from an array.
	 * Specify a seed, so you get the same random value every time (if the same seed is used).
	 *
	 * @param array $array The array to get a random value from.
	 * @param int   $seed The seed to use. If not specified, a 'real' random value will be used.
	 *
	 * @return mixed
	 */
	private static function get_random( array $array, int $seed = -1 ) {
		$rand_key = -1 !== $seed
			? $seed % ( count( $array ) - 1 )
			: array_rand( $array );
		return $array[ $rand_key ];
	}

	/**
	 * Calculate if a color is light (or dark).
	 *
	 * @see https://stackoverflow.com/questions/3942878/how-to-decide-font-color-in-white-or-black-depending-on-background-color/3943023
	 *
	 * @param string $color The HEX color to check.
	 * @return boolean True if the color is light, false if it is dark.
	 */
	private static function is_color_light( string $color ) {
		list( $r, $g, $b ) = sscanf( ltrim( $color, '#' ), '%02x%02x%02x' );
		return ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) > 145;
	}

	/**
	 * Get the initials of a name.
	 *
	 * @param string $name Like "Jane Doe" or "Jane Margret Tess K. Doe".
	 * @param int    $limit Removes middle names, then last name.
	 *
	 * @return string like JD
	 */
	private static function get_initials( string $name, int $limit = 2 ) {
		if ( ! mb_check_encoding( $name, 'UTF-8' ) ) {
			$encoding = mb_detect_encoding( $name, null, true );
			$name     = iconv( $encoding, 'UTF-8//TRANSLIT', $name );
		}

		$letters_left = array_map( array( self::class, 'first_letter' ), explode( ' ', $name ) );
		if ( 1 === $limit || 1 === count( $letters_left ) ) {
			return $letters_left[0];
		}
		// Get first and last, remove them from $letters_left.
		$new_letters = array( array_shift( $letters_left ), array_pop( $letters_left ) );
		if ( $limit > 2 ) {
			$between = array_splice( $letters_left, 0, $limit - 2 ); // get the middle names.
			array_splice( $new_letters, 1, 0, $between ); // insert between first and last name.
		}

		return implode( '', $new_letters );
	}

	/**
	 * Get the first letter of a string.
	 * Took me a while: if you use $name[0] you break emojis and other unicode characters (like ä).
	 *
	 * @param string $name like "Jane".
	 * @return string like J.
	 */
	private static function first_letter( string $name ) {
		return mb_substr( $name, 0, 1 );
	}
}
