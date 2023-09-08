<!DOCTYPE html><html<?php
	if ( ! empty( $rtl ) ) echo ' dir="rtl"';
	if ( ! empty( $lang ) ) echo ' lang="'.$lang.'"';
?>><head><meta charset="utf-8">

<?php if ( ! empty( $head_title ) ) echo '<title>'.$head_title.'</title>'; ?>
<?php if ( ! empty( $head_callback ) && is_callable( $head_callback ) ) call_user_func( $head_callback, $profile ?? FALSE ); ?>
</head>
<body<?php if ( ! empty( $body_class ) ) echo ' class="'.$body_class.'"'; ?>>
<div<?php if ( ! empty( $wrap_class ) ) echo ' class="'.$wrap_class.'"'; ?>>
