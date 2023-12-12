<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait DefaultTerms
{

	protected function init_default_terms()
	{
		if ( ! method_exists( $this, 'define_default_terms' ) )
			return FALSE;

		foreach ( $this->define_default_terms() as $constant => $terms )
			$this->register_default_terms(
				$this->constant( $constant ),
				$this->get_default_terms( $constant, $terms )
			);
	}

	// protected function define_default_terms() { return []; }

	protected function get_default_terms( $constant, $terms = NULL )
	{
		// constant is not defined (in case custom terms are for another modules)
		if ( ! $this->constant( $constant ) )
			return [];

		if ( is_null( $terms ) ) {

			$defaults = NULL;

			if ( method_exists( $this, 'define_default_terms' ) )
				$defaults = $this->define_default_terms();

			if ( $defaults && array_key_exists( $constant, $defaults ) )
				$terms = $defaults[$constant];

			// DEPRECATED: use `$this->define_default_terms()`
			else if ( ! empty( $this->strings['default_terms'][$constant] ) )
				$terms = $this->strings['default_terms'][$constant];

			// DEPRECATED: use `$this->define_default_terms()`
			else if ( ! empty( $this->strings['terms'][$constant] ) )
				$terms = $this->strings['terms'][$constant];

			else
				$terms = [];
		}

		// NOTE: hook filter before `init` on `after_setup_theme`
		return $this->filters( 'get_default_terms', $terms, $this->constant( $constant ) );
	}

	protected function register_default_terms( $taxonomy, $terms )
	{
		if ( ! defined( 'GNETWORK_VERSION' ) || ! $taxonomy )
			return FALSE;

		if ( ! is_admin() )
			return FALSE;

		if ( empty( $terms ) )
			return FALSE;

		add_filter( sprintf( 'gnetwork_taxonomy_default_terms_%s', $taxonomy ),
			static function ( $pre ) use ( $terms ) {
				return array_merge( $pre, Core\Arraay::isAssoc( $terms ) ? $terms : Core\Arraay::sameKey( $terms ) );
			} );
	}
}
