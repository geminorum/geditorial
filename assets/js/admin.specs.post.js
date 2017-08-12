(function ($, p, c, m) {
  "use strict";

  var s = {
    action: p._base + '_' + m,
    classs: p._base + '-' + m,

    wrap: 'ol.' + p._base + '-' + m + '-list',
    raw: 'ul.' + p._base + '-' + m + '-new',
    body: '.item-body'
  };

  var o = {

    // http://stackoverflow.com/a/14736775
    reOrder: function() {
      var inputs = $('input.item-order');
      var nbElems = inputs.length;
      inputs.each(function(idx) {
        $(this).val(nbElems - idx);
      });
    },

    expandItem: function(element){
      $(s.body, s.wrap).slideUp();
      var clicked = $(element).parent().parent().find(s.body);
      if (!clicked.is(':visible')) {
        clicked.slideDown();
      }
    },

    removeItem: function(element){
      // FIXME: must remove disable from ul selector
      $(element).closest('li').slideUp('normal', function() {
        $(this).remove();
      });
    },

    newItem: function(element){

      if ($(element).parents(s.wrap).length) {
        return false;
      };

      var selectedVal = $(element).find(':selected').val();

      if (selectedVal === '-1') {
        return false;
      };

      $(s.body, s.wrap).slideUp();
      var row = $('li', s.raw).clone(true);

      $(element).find(':selected').prop('disabled', true);

      row.find('select.item-dropdown-new').removeClass('item-dropdown-new');
      row.find('span.item-excerpt').html($(element).find(':selected').text());
      row.find('select.item-dropdown option[value="-1"]').remove();
      row.find('select.item-dropdown option[value="' + selectedVal + '"]').selected = true;

      row.appendTo(s.wrap);

      row.find(s.body).slideDown();
      row.find('textarea').focus();

      this.reOrder();
    }

  };

  $(document).ready(function() {

    $('select.item-dropdown-new', s.raw).change(function() {
      return o.newItem(this);
    });

    $('body').on('click', s.wrap + ' .item-delete', function(e) {
      e.preventDefault();
      o.removeItem(this);
    });

    $('body').on('click', s.wrap + ' .item-excerpt', function(e) {
      e.preventDefault();
      o.expandItem(this);
    });

    $(s.wrap).sortable({
      // disable: true,
      group: s.classs,
      handle: '.item-handle',
      stop: function() {
        o.reOrder();
      }
    // }).disableSelection();
    });
  });

  c[m] = o;

  if (p._dev) {
    console.log(o);
  }

}(jQuery, gEditorial, gEditorialModules, 'specs'));
