<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

// Direct known sub-classes of `Exception`
// `RuntimeException`
// `BadFunctionCallException`
// `DomainException`
// `InvalidArgumentException`
// `LengthException`
// `OutOfRangeException`

// NOTE: DEPRECATED: use `\Exception` directly
class Exception extends \Exception {}
