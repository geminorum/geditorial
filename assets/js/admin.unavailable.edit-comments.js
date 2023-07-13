(function ($) {
  $('.wp-list-table').on('click', '.comment_doarchive a, .comment_unarchive a', function (e) {
    e.preventDefault();
    const link = $(this);
    if (link.prop('disabled')) {
      return false;
    }

    $.ajax({
      type: 'GET',
      url: link.attr('href'),
      beforeSend: function (xhr) {
        link.text(link.data('loading'));
        link.prop('disabled', true);
      },
      success: function (response) {
        if (response.success) {
          $('tr#comment-' + link.data('id')).fadeOut('slow');
        } else {
          link.text(link.data('error'));
          link.attr('href', 'javascript:void(0)');
          link.addClass('-error');
          console.log(response);
        }
      }
    });
  });
}(jQuery));
