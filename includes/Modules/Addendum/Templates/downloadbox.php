<?php defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) ); ?>

<div class="downloadbox">
	<a href="<?php echo esc_url( get_permalink() ); ?>" download="<?php echo esc_attr( get_the_title() ); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="flex-shrink-0" viewBox="0 0 16 16">
	  <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z" />
	  <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z" />
	</svg></a>
	<div class="-content">
	  <div>
		<h6 class="-title"><a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo get_the_title(); ?></a></h6>
		<div class="-description">
			<?php echo wpautop( $post->post_excerpt ); ?>
		</div>
	  </div>
	  <?php \geminorum\gEditorial\Modules\Addendum\ModuleTemplate::downloadFileSize(); ?>
	</div>
</div>
