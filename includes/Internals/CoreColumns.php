<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait CoreColumns
{
	// Checks to bail early if column is hidden
	protected function check_hidden_column( false|string $column, string $after = '' ): bool
	{
		if ( FALSE === $column )
			return FALSE;

		return gEditorial\Listtable::checkHidden(
			$column ?? $this->classs(),
			$after
		);
	}

	public function get_column_title( string $column, ?string $constant = NULL, mixed $fallback = NULL ): string
	{
		return $this->filters( 'column_title',
			$this->get_string(
				self::und( $column, 'column_title' ),
				$constant,
				'misc',
				$fallback ?? $column
			),
			$column,
			$constant,
			$fallback
		);
	}

	public function get_column_title_posttype( string $constant, false|string $taxonomy = FALSE, mixed $fallback = NULL ): string
	{
		return $this->filters( 'column_title',
			Services\CustomPostType::getLabel(
				$this->constant( $constant ),
				'column_title',
				'name',
				$fallback
			),
			$taxonomy,
			$constant,
			$fallback
		);
	}

	public function get_column_title_taxonomy(
		string $constant,
		false|string $posttype = FALSE,
		mixed $fallback = NULL,
	): string {

		return $this->filters( 'column_title',
			Services\CustomTaxonomy::getLabel(
				$this->constant( $constant ),
				'column_title',
				'name',
				$fallback
			),
			$posttype,
			$constant,
			$fallback
		);
	}

	public function get_column_title_icon(
		string $column,
		?string $constant = NULL,
		mixed $fallback = NULL,
	): string {

		$title = $this->get_column_title( $column, $constant, $fallback );

		return sprintf(
			'<span class="-column-icon %3$s" title="%2$s">%1$s</span>',
			$title,
			esc_attr( $title ),
			$this->classs( $column )
		);
	}

	public function get_column_icon(
		false|string $link = FALSE,
		null|string|array $icon = NULL,
		?string $title = NULL,
		string $posttype = 'post',
		array|string $extra = [],
	): string {

		return Core\HTML::tag( ( $link ? 'a' : 'span' ), [
			'href'   => $link ?: FALSE,
			'title'  => $title ?? $this->get_string( 'column_icon_title', $posttype, 'misc', $this->module->title ),
			'class'  => array_merge( [ '-icon', ( $link ? '-link' : '-info' ) ], (array) $extra ),
			'target' => $link ? '_blank' : FALSE,
		], Services\Icons::get( $icon ?? $this->module->icon ) );
	}
}
