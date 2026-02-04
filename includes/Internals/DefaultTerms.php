<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait DefaultTerms
{
	protected function init_default_terms()
	{
		if ( ! method_exists( $this, 'define_default_terms' ) )
			return FALSE;

		$this->filter( 'taxonomy_default_terms', 2, 12, FALSE, 'gnetwork' );
	}

	public function taxonomy_default_terms( $terms, $taxonomy )
	{
		foreach ( $this->define_default_terms() as $constant => $defaults )
			if ( $taxonomy === $this->constant( $constant ) )
				return $terms + $this->get_default_terms( $constant,
					Core\Arraay::isList( $defaults )
						? Core\Arraay::sameKey( $defaults )
						: $defaults
					);

		return $terms;
	}

	// protected function define_default_terms() { return []; }

	protected function get_default_terms( $constant, $terms = NULL )
	{
		// Constant is not defined (in case custom terms are for another module)
		if ( ! $this->constant( $constant ) )
			return []; // must return empty

		if ( is_null( $terms ) ) {

			$defaults = NULL;

			if ( method_exists( $this, 'define_default_terms' ) )
				$defaults = $this->define_default_terms();

			if ( $defaults && array_key_exists( $constant, $defaults ) )
				$terms = $defaults[$constant];

			// NOTE: DEPRECATED: use `$this->define_default_terms()`
			else if ( ! empty( $this->strings['default_terms'][$constant] ) )
				$terms = $this->strings['default_terms'][$constant];

			// NOTE: DEPRECATED: use `$this->define_default_terms()`
			else if ( ! empty( $this->strings['terms'][$constant] ) )
				$terms = $this->strings['terms'][$constant];

			else
				$terms = [];
		}

		// NOTE: hook filter before `init` on `after_setup_theme`
		return $this->filters( 'get_default_terms',
			$terms,
			$this->constant( $constant )
		);
	}
}
