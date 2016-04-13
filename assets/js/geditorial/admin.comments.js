jQuery(document).ready(function($) {
	$('.geditorial-comments-row-action').click(function() {
		$this = $(this);
		$.post(
			ajaxurl, {
				'action': 'geditorial_comments',
				'do': $this.attr('data-do'),
				'comment_id': $this.attr('data-comment_id')
			},
			function(response) {
				var action = $this.attr('data-do'),
					comment_id = $this.attr('data-comment_id'),
					$comment = $("#comment-" + comment_id + ", #li-comment-" + comment_id),
					$this_and_comment = $this.siblings('.geditorial-comments-row-action').add($comment).add($this);
				if (action == 'feature')
					$this_and_comment.addClass('featured');
				if (action == 'unfeature')
					$this_and_comment.removeClass('featured');
				if (action == 'bury')
					$this_and_comment.addClass('buried');
				if (action == 'unbury')
					$this_and_comment.removeClass('buried');
			}
		);
		return false;
	});

	/* Set classes on Edit Comments */
	$('.geditorial-comments-row-action.feature').each(function() {
		$this = $(this);
		$tr = $(this).parents('tr');
		if ($this.hasClass('featured')) $tr.addClass('featured');
		if ($this.hasClass('buried')) $tr.addClass('buried');
	});
});
