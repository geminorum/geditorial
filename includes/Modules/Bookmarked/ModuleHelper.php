<?php namespace geminorum\gEditorial\Modules\Bookmarked;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Visual;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{
	const MODULE = 'bookmarked';

	public static function prepDataForSummary( $data, $options, $context )
	{
		$prepped = [];

		foreach ( $data as $row ) {

			$type   = empty( $row['__type'] ) ? 'default' : $row['__type'];
			$option = empty( $options[$type] ) ? [] : $options[$type];

			$item = [
				'type'    => $type,
				'label'   => '',
				'link'    => '',
				'desc'    => '',
				'code'    => array_key_exists( '__code', $row ) ? Core\Number::translate( $row['__code'] ) : '',
				'icon'    => array_key_exists( 'icon', $option ) ? $option['icon'] : '',
				'logo'    => array_key_exists( 'logo', $option ) ? $option['logo'] : '',
				'title'   => array_key_exists( 'title', $option ) ? $option['title'] : '',
				'color'   => 'inherit',
				'bgcolor' => 'transparent',
			];

			if ( ! empty( $row['__label'] ) )
				$item['label'] = Core\Text::wordWrap( Core\Text::trim( $row['__label'] ) );

			else if ( ! empty( $option['title'] ) )
				$item['label'] = Core\Text::wordWrap( $option['title'] );

			if ( ! empty( $row['__link'] ) )
				$item['link'] = Core\URL::sanitize( $row['__link'] );

			else if ( ! empty( $option['template'] ) )
				$item['link'] = Core\Text::replaceTokens( $option['template'], $row );

			if ( ! empty( $row['__desc'] ) )
				$item['desc'] = Core\Text::wordWrap( WordPress\Strings::prepDescription( Core\Text::replaceTokens( $row['__desc'], $row ) ) );

			else if ( 'attachment' === $type && ! empty( $row['__code'] ) )
				$item['desc'] = Core\Text::wordWrap( WordPress\Strings::prepDescription( WordPress\Attachment::caption( (int) $row['__code'], '' ) ) );

			if ( empty( $item['desc'] ) && ! empty( $option['desc'] ) )
				$item['desc'] = Core\Text::wordWrap( WordPress\Strings::prepDescription( Core\Text::replaceTokens( $option['desc'], $row ) ) );

			if ( 'attachment' === $type && ! empty( $row['__code'] ) )
				$item['icon'] = Core\Icon::guessByMIME( WordPress\Attachment::type( (int) $row['__code'] ), $item['icon'] );

			$item['icon'] = Visual::getIcon( $item['icon'], 'external' );

			if ( ! empty( $option['color'] ) && Core\Color::validHex( $option['color'] ) ) {
				$item['color']   = $option['color'];
				$item['bgcolor'] = Core\Color::lightOrDark( $option['color'] );
			}

			$prepped[] = $item;
		}

		return $prepped;
	}

	// @SEE: `subcontent_define_type_options()`
	public static function getTypeOptions( $context = NULL, $path = NULL )
	{
		return [
			[
				'name'     => 'default',
				'title'    => _x( 'Custom Link', 'Type Option', 'geditorial-bookmarked' ),
				'template' => '{{link}}',
				'cssclass' => '-custom-link',
				'icon'     => [ 'misc-16', 'box-arrow-up-right' ],
				'logo'     => '',
			],
			[
				'name'     => 'post',
				'title'    => _x( 'Site Post', 'Type Option', 'geditorial-bookmarked' ),
				'template' => Core\WordPress::getPostShortLink( '{{code}}' ),
				'cssclass' => '-internal-post',
				'icon'     => 'admin-post',
				'logo'     => '',
			],
			[
				'name'     => 'attachment',
				'title'    => _x( 'Site Attachment', 'Type Option', 'geditorial-bookmarked' ),
				'template' => Core\WordPress::getPostShortLink( '{{code}}' ),
				'cssclass' => '-internal-attachment',
				'icon'     => 'media-default',
				'logo'     => '',
			],
			[
				'name'     => 'term',
				'title'    => _x( 'Site Term', 'Type Option', 'geditorial-bookmarked' ),
				'template' => Core\WordPress::getTermShortLink( '{{code}}' ),
				'cssclass' => '-internal-term',
				'icon'     => 'tag',
				'logo'     => '',
			],
			[
				'name'     => 'nlai',
				'title'    => _x( 'National Library', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://opac.nlai.ir/opac-prod/bibliographic/{{code}}',
				'cssclass' => '-national-library',
				'icon'     => [ 'misc-88', 'nlai.ir' ],
				'logo'     => '',
				'desc'     => _x( 'See the page about this on National Library website.', 'Type Description', 'geditorial-bookmarked' ),
				'color'    => '#c8b131',
			],
			[
				'name'     => 'goodreads',
				'title'    => _x( 'Goodreads Book', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://www.goodreads.com/book/show/{{code}}',
				'cssclass' => '-goodreads-book',
				'icon'     => [ 'misc-24', 'goodreads' ], // 'amazon',
				'logo'     => self::getLinkLogo( 'goodreads', NULL, $context, $path ),
				'desc'     => _x( 'More about this on Goodreads network.', 'Type Description', 'geditorial-bookmarked' ),
				'color'    => '#59461b',
			],
			[
				'name'     => 'fidibo',
				'title'    => _x( 'Fidibo Book', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://fidibo.com/book/{{code}}',
				'cssclass' => '-fidibo-book',
				'icon'     => [ 'misc-16', 'fidibo' ], // 'location-alt',
				'logo'     => self::getLinkLogo( 'fidibo', NULL, $context, $path ),
				'desc'     => _x( 'Read this on Fidibo e-book platform.', 'Type Description', 'geditorial-bookmarked' ),
				'color'    => '#322e2a',
			],
			[
				'name'     => 'taaghche',
				'title'    => _x( 'Taaghche Book', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://taaghche.com/book/{{code}}',
				'cssclass' => '-taaghche-book',
				'icon'     => [ 'misc-16', 'taaghche' ], // 'book-alt',
				'logo'     => self::getLinkLogo( 'taaghche', NULL, $context, $path ),
				'desc'     => _x( 'Read this on Taaghche e-book platform.', 'Type Description', 'geditorial-bookmarked' ),
				'color'    => '#00a2a4',
			],
			[
				'name'     => 'behkhaan_book',
				'title'    => _x( 'Behkhaan Book', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://behkhaan.ir/books/{{code}}',
				'cssclass' => '-behkhaan-profile',
				'icon'     => [ 'misc-32', 'behkhaan' ], // 'book-alt',
				'logo'     => self::getLinkLogo( 'behkhaan', 'png', $context, $path ),
				'desc'     => _x( 'More about this book on Behkhaan network.', 'Type Description', 'geditorial-bookmarked' ),
				'color'    => '#2bae66',
			],
			[
				'name'     => 'behkhaan_profile',
				'title'    => _x( 'Behkhaan Profile', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://behkhaan.ir/profile/{{code}}',
				'cssclass' => '-behkhaan-profile',
				'icon'     => [ 'misc-32', 'behkhaan' ], // 'book-alt',
				'logo'     => self::getLinkLogo( 'behkhaan', 'png', $context, $path ),
				'desc'     => _x( 'More about this person on Behkhaan network.', 'Type Description', 'geditorial-bookmarked' ),
				'color'    => '#2bae66',
			],
			[
				'name'     => 'neshan',
				'title'    => _x( 'Neshan Map', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://nshn.ir/{{code}}',
				'cssclass' => '-neshan-map',
				'icon'     => [ 'misc-512', 'neshan' ], // 'location-alt',
				'logo'     => self::getLinkLogo( 'neshan', NULL, $context, $path ),
				'desc'     => _x( 'More about this on Neshan maps.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'balad',
				'title'    => _x( 'Balad Map', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://balad.ir/p/{{code}}',
				'cssclass' => '-balad-map',
				'icon'     => [ 'misc-512', 'balad' ], // 'location-alt',
				'logo'     => self::getLinkLogo( 'balad', NULL, $context, $path ),
				'desc'     => _x( 'More about this on Balad maps.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'aparat',
				'title'    => _x( 'Aparat Video', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://www.aparat.com/v/{{code}}',
				'cssclass' => '-aparat-video',
				'icon'     => [ 'misc-24', 'aparat' ], // 'video-alt3',
				'logo'     => self::getLinkLogo( 'aparat', NULL, $context, $path ),
				'desc'     => _x( 'More about this on Aparat video platform.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'youtube',
				'title'    => _x( 'Youtube Video', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://www.youtube.com/watch?v={{code}}',
				'cssclass' => '-youtube-video',
				'icon'     => 'youtube',
				'logo'     => self::getLinkLogo( 'youtube', NULL, $context, $path ),
				'desc'     => _x( 'More about this on Youtube video platform.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'wikipedia',
				'title'    => _x( 'Wikipedia Page', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://{{_iso639}}.wikipedia.org/wiki/{{code}}',
				'cssclass' => '-wikipedia-page',
				'icon'     => [ 'misc-16', 'wikipedia' ],
				'logo'     => self::getLinkLogo( 'wikipedia', NULL, $context, $path ),
				'desc'     => _x( 'More about this on an Wikipedia page.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'letterboxd',
				'title'    => _x( 'Letterboxd Page', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://letterboxd.com/film/{{code}}',
				'cssclass' => '-letterboxd-page',
				'icon'     => [ 'misc-32', 'letterboxd' ],
				'logo'     => self::getLinkLogo( 'letterboxd', NULL, $context, $path ),
				'desc'     => _x( 'More about this on a Letterboxd page.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'imdb',
				'title'    => _x( 'IMDB Page', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://www.imdb.com/find/?q={{code}}',
				'cssclass' => '-imdb-page',
				'icon'     => [ 'misc-32', 'imdb' ],
				'logo'     => self::getLinkLogo( 'imdb', NULL, $context, $path ),
				'desc'     => _x( 'More about this on an IMDB page.', 'Type Description', 'geditorial-bookmarked' ),
				'color'    => '#f5c518',
			],
			[
				'name'     => 'tmdb_movie',
				'title'    => _x( 'TMDB Movie', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://www.themoviedb.org/movie/{{code}}',
				'cssclass' => '-tmdb-movie',
				'icon'     => [ 'misc-32', 'tmdb' ],
				'logo'     => self::getLinkLogo( 'tmdb', NULL, $context, $path ),
				'desc'     => _x( 'More about this film on The Movie Database.', 'Type Description', 'geditorial-bookmarked' ),
				'color'    => '#01b4e4',
			],
			[
				'name'     => 'tmdb_person',
				'title'    => _x( 'TMDB Person', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'www.themoviedb.org/person/{{code}}',
				'cssclass' => '-tmdb-person',
				'icon'     => [ 'misc-32', 'tmdb' ],
				'logo'     => self::getLinkLogo( 'tmdb', NULL, $context, $path ),
				'desc'     => _x( 'More about this person on The Movie Database.', 'Type Description', 'geditorial-bookmarked' ),
				'color'    => '#01b4e4',
			],
			[
				'name'     => 'navaar',
				'title'    => _x( 'Navaar Audio', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://www.navaar.ir/audiobook/{{code}}',
				'cssclass' => '-navaar-audio',
				'icon'     => [ 'misc-32', 'navaar' ],
				'logo'     => self::getLinkLogo( 'navaar', NULL, $context, $path ),
				'desc'     => _x( 'More about this on an Navaar audio.', 'Type Description', 'geditorial-bookmarked' ),
				'color'    => '#567dbf',
			],
			[
				'name'     => 'email',
				'title'    => _x( 'Email Address', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'mailto::{{code}}', // @SEE: `Core\HTML::mailto()`
				'cssclass' => '-email-address',
				'icon'     => 'email',
				'logo'     => '',
				'desc'     => _x( 'Contact someone about this via email.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'phone',
				'title'    => _x( 'Phone Number', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'tel::{{code}}', // @SEE: `Core\HTML::tel()`
				'cssclass' => '-phone-number',
				'icon'     => 'phone',
				'logo'     => '',
				'desc'     => _x( 'Contact someone about this via phone.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'mobile',
				'title'    => _x( 'Mobile Number', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'tel::{{code}}', // @SEE: `Core\HTML::prepURLforTel()`
				'cssclass' => '-mobile-number',
				'icon'     => 'smartphone',
				'logo'     => '',
				'desc'     => _x( 'Contact someone about this via mobile phone.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'sms',
				'title'    => _x( 'SMS Number', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'sms::{{code}}', // @SEE: `Core\HTML::prepURLforSMS()`
				'cssclass' => '-sms-number',
				'icon'     => 'text',
				'logo'     => '',
				'desc'     => _x( 'Contact someone about this via short message.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'pdf',
				'title'    => _x( 'PDF Document', 'Type Option', 'geditorial-bookmarked' ),
				'template' => '{{link}}',
				'cssclass' => '-pdf-document',
				'icon'     => [ 'misc-16', 'filetype-pdf' ],
				'logo'     => '',
				'desc'     => _x( 'Read more about this as PDF format.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'epub',
				'title'    => _x( 'ePub Book', 'Type Option', 'geditorial-bookmarked' ),
				'template' => '{{link}}',
				'cssclass' => '-epub-book',
				'icon'     => [ 'misc-32', 'epub' ],
				'logo'     => self::getLinkLogo( 'epub', NULL, $context, $path ),
				'desc'     => _x( 'Read more about this as ePub book.', 'Type Description', 'geditorial-bookmarked' ),
				'color'    => '#86b918',
			],
		];
	}

	public static function getLinkLogo( $key, $ext = NULL, $context = NULL, $path = NULL )
	{
		return sprintf( '%s%s%s.%s',
			Core\URL::fromPath( $path ?? self::path( $context ) ),
			'data/logos/',
			$key,
			$ext ?? self::const( 'SCRIPT_DEBUG' ) ? 'svg' : 'min.svg'
		);
	}
}
