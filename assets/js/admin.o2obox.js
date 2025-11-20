/* eslint no-unused-vars: off */
/* global Mustache, Backbone */

(function ($, plugin, module, section) {
  const ENTER_KEY = 13;

  // const s = {
  //   action: plugin._base + '_' + module,
  //   classs: plugin._base + '-' + module
  // };

  const app = {
    rtl: $('html').attr('dir') === 'rtl',
    strings: $.extend({}, {
      confirm: 'Are you sure you want to delete all connections?'
    }, plugin[module].strings || {})
  };

  const rowWait = function ($td) {
    return $td.find('.o2o-icon').css('background-image', 'url(' + plugin[module]._spinner + ')');
  };

  const removeRow = function ($td) {
    const $table = $td.closest('table');
    $td.closest('tr').remove();
    if (!$table.find('tbody tr').length) {
      return $table.hide();
    }
  };

  const getMustacheTemplate = function (name) {
    return $('#o2o-template-' + name).html();
  };

  // Class for representing a single connection candidate
  const Candidate = Backbone.Model.extend({});

  // Class for representing a single connection
  const Connection = Backbone.Model.extend({});

  // Class for holding search parameters; not really a model
  const Candidates = Backbone.Model.extend({

    // (Re)perform a search with the current parameters
    sync: function () {
      const _this = this;
      const params = {
        subaction: 'search'
      };
      return this.ajaxRequest(params, function (response) {
        const _ref = response.navigation;
        _this.total_pages = (_ref ? _ref['total-pages-raw'] : undefined) || 1;
        _this.trigger('sync', response);
      });
    },

    // Validation function, called by Backbone when parameters are changed
    validate: function (attrs) {
      const _ref = attrs.paged;
      if (_ref > 0 && _ref <= this.total_pages) {
        return null;
      }
      return 'invalid page';
    }
  });

  // Class for holding a list of connections
  const Connections = Backbone.Collection.extend({
    model: Connection,

    // Create both a candidate item and a connection
    createItemAndConnect: function (title) {
      const _this = this;
      const data = {
        subaction: 'create_post',
        post_title: title
      };
      return this.ajaxRequest(data, function (response) {
        _this.trigger('create', response);
      });
    },

    // Create a connection from a candidate
    create: function (candidate) {
      const _this = this;
      const data = {
        subaction: 'connect',
        to: candidate.get('id')
      };
      return this.ajaxRequest(data, function (response) {
        _this.trigger('create', response);
      });
    },

    // Delete a connection
    delete: function (connection) {
      const _this = this;
      const data = {
        subaction: 'disconnect',
        o2o_id: connection.get('id')
      };
      return this.ajaxRequest(data, function (response) {
        _this.trigger('delete', response, connection);
      });
    },

    // Delete all connections
    clear: function () {
      const _this = this;
      const data = {
        subaction: 'clear_connections'
      };
      return this.ajaxRequest(data, function (response) {
        _this.trigger('clear', response);
      });
    }
  });

  // View responsible for the connection list
  const ConnectionsView = Backbone.View.extend({

    events: {
      'click th.o2o-col-delete .o2o-icon': 'clear',
      'click td.o2o-col-delete .o2o-icon': 'delete'
    },

    initialize: function (options) {
      this.options = options;
      this.maybe_make_sortable();
      this.collection.on('create', this.afterCreate, this);
      this.collection.on('clear', this.afterClear, this);
    },

    maybe_make_sortable: function () {
      if (this.$('th.o2o-col-order').length) {
        this.$('tbody').sortable({
          handle: 'td.o2o-col-order',
          helper: function (e, ui) {
            ui.children().each(function () {
              const $this = $(this);
              $this.width($this.width());
            });
            return ui;
          }
        });
      }
    },

    clear: function (ev) {
      ev.preventDefault();
      if (!confirm(app.strings.confirm)) {
        return;
      }
      const $td = $(ev.target).closest('td');
      rowWait($td);
      this.collection.clear();
    },

    afterClear: function () {
      this.$el.hide().find('tbody').html('');
    },

    delete: function (ev) {
      ev.preventDefault();
      const $td = $(ev.target).closest('td');
      rowWait($td);
      const req = this.collection.delete(new Connection({
        id: $td.find('input').val()
      }));
      req.done(function () {
        removeRow($td);
      });
    },

    afterCreate: function (response) {
      this.$el.show().find('tbody').append(response.row);
      this.collection.trigger('append', response);
    }
  });

  // View responsible for the candidate list
  const CandidatesView = Backbone.View.extend({

    // template: Mustache.compile(getMustacheTemplate('tab-list')),
    // template: Mustache.render(getMustacheTemplate('tab-list')),
    // template: Mustache.parse(getMustacheTemplate('tab-list')),
    template: getMustacheTemplate('tab-list'),

    events: {
      'keypress :text': 'handleReturn',
      'keyup :text': 'handleSearch',
      'click .o2o-prev, .o2o-next': 'changePage',
      'click td.o2o-col-create div': 'promote'
    },

    initialize: function (options) {
      this.options = options;
      this.spinner = options.spinner;
      options.connections.on('delete', this.afterCandidatesRefreshed, this);
      options.connections.on('clear', this.afterCandidatesRefreshed, this);
      this.collection.on('sync', this.afterCandidatesRefreshed, this);
      this.collection.on('error', this.afterInvalid, this);
      this.collection.on('invalid', this.afterInvalid, this);
    },

    promote: function (ev) {
      const _this = this;
      ev.preventDefault();
      const $td = $(ev.target).closest('td');
      rowWait($td);
      const candidate = new Candidate({
        id: $td.find('div').data('item-id')
      });
      const req = this.options.connections.create(candidate);
      req.done(function () {
        if (_this.options.duplicate_connections) {
          $td.find('.o2o-icon').css('background-image', '');
        } else {
          removeRow($td);
        }
      });
    },

    handleReturn: function (ev) {
      if (ev.keyCode === ENTER_KEY) {
        ev.preventDefault();
      }
    },

    handleSearch: function (ev) {
      const _this = this;
      let delayed; // eslint-disable-line
      if (delayed !== undefined) {
        clearTimeout(delayed);
      }
      const $searchInput = $(ev.target);
      delayed = setTimeout(function () {
        const searchStr = $searchInput.val();
        if (searchStr === _this.collection.get('s')) {
          return;
        }
        _this.spinner.insertAfter($searchInput).show();
        _this.collection.save({
          s: searchStr,
          paged: 1
        });
      }, 400);
    },

    changePage: function (ev) {
      const $navButton = $(ev.currentTarget);
      let newPage = this.collection.get('paged');
      if ($navButton.hasClass('o2o-prev')) {
        newPage--;
      } else {
        newPage++;
      }
      this.spinner.appendTo(this.$('.o2o-navigation'));
      this.collection.save('paged', newPage);
    },

    afterCandidatesRefreshed: function (response) {
      this.spinner.remove();
      this.$('button, .o2o-results, .o2o-navigation, .o2o-notice').remove();
      if (typeof response !== 'string') {
        // response = this.template(response);
        response = Mustache.render(this.template, response, {
          'table-row': getMustacheTemplate('table-row')
        });
      }
      this.$el.append(response);
    },
    afterInvalid: function () {
      this.spinner.remove();
    }
  });

  // View responsible for the post creation UI
  const CreatePostView = Backbone.View.extend({

    events: {
      'click button': 'createItem',
      'keypress :text': 'handleReturn'
    },

    initialize: function (options) {
      this.options = options;
      this.createButton = this.$('button');
      this.createInput = this.$(':text');
    },

    handleReturn: function (ev) {
      if (ev.keyCode === ENTER_KEY) {
        this.createButton.click();
        ev.preventDefault();
      }
    },

    createItem: function (ev) {
      const _this = this;
      ev.preventDefault();
      if (this.createButton.hasClass('inactive')) {
        return false;
      }
      const title = this.createInput.val();
      if (title === '') {
        this.createInput.focus();
        return;
      }
      this.createButton.addClass('inactive');
      const req = this.collection.createItemAndConnect(title);
      req.done(function () {
        _this.createInput.val('');
        _this.createButton.removeClass('inactive');
      });
    }
  });

  // View responsible for the entire meta-box
  const MetaboxView = Backbone.View.extend({

    events: {
      'click .o2o-toggle-tabs': 'toggleTabs',
      'click .wp-tab-bar li': 'setActiveTab'
    },

    initialize: function (options) {
      this.options = options;
      this.spinner = options.spinner;
      this.initializedCandidates = false;
      options.connections.on('append', this.afterConnectionAppended, this);
      options.connections.on('clear', this.afterConnectionDeleted, this);
      options.connections.on('delete', this.afterConnectionDeleted, this);
    },

    toggleTabs: function (ev) {
      ev.preventDefault();
      const $tabs = this.$('.o2o-create-connections-tabs');
      $tabs.toggle();
      if (!this.initializedCandidates && $tabs.is(':visible')) {
        this.options.candidates.sync();
        this.initializedCandidates = true;
      }
    },

    setActiveTab: function (ev) {
      ev.preventDefault();
      const $tab = $(ev.currentTarget);
      this.$('.wp-tab-bar li').removeClass('wp-tab-active');
      $tab.addClass('wp-tab-active');
      this.$el.find('.tabs-panel').hide().end().find($tab.data('ref')).show().find(':text').focus();
    },

    afterConnectionAppended: function (response) {
      if (this.options.cardinality === 'one') {
        this.$('.o2o-create-connections').hide();
      }
    },

    afterConnectionDeleted: function (response) {
      if (this.options.cardinality === 'one') {
        this.$('.o2o-create-connections').show();
      }
    }
  });

  window.O2OAdmin = {
    Candidate,
    Connection,
    boxes: {}
  };

  $(function () {
    // Mustache.compilePartial('table-row', getMustacheTemplate('table-row'));
    // Mustache.render('table-row', getMustacheTemplate('table-row'));

    $('.o2o-box').each(function () {
      const $metabox = $(this);
      const $spinner = $('<img>', {
        src: plugin[module]._spinner,
        class: 'o2o-spinner'
      });

      const candidates = new Candidates({
        s: '',
        paged: 1
      });

      candidates.total_pages = $metabox.find('.o2o-total').data('num') || 1;

      const ctype = {
        o2o_type: $metabox.data('o2o_type'),
        direction: $metabox.data('direction'),
        from: $('#post_ID').val()
      };

      // All Ajax requests should be done through this function
      function ajaxRequest (options, callback) {
        const params = $.extend({}, options, candidates.attributes, ctype, {
          action: 'o2o_box',
          nonce: plugin[module]._nonce
        });

        return $.post(ajaxurl, params, function (response) {
          // let e;

          try {
            response = JSON.parse(response);
          } catch (_error) {
            // e = _error;
            if (typeof console !== 'undefined' && console !== null) {
              console.error('Malformed response', response);
            }
            return;
          }

          if (response.error) {
            return alert(response.error);
          } else {
            return callback(response);
          }
        });
      }

      candidates.ajaxRequest = ajaxRequest;

      const connections = new Connections();
      connections.ajaxRequest = ajaxRequest;

      const connectionsView = new ConnectionsView({
        el: $metabox.find('.o2o-connections'),
        collection: connections,
        candidates
      });

      const candidatesView = new CandidatesView({
        el: $metabox.find('.o2o-tab-search'),
        collection: candidates,
        connections,
        spinner: $spinner,
        duplicate_connections: $metabox.data('duplicate_connections')
      });

      const createPostView = new CreatePostView({
        el: $metabox.find('.o2o-tab-create-post'),
        collection: connections
      });

      const metaboxView = new MetaboxView({
        el: $metabox,
        spinner: $spinner,
        cardinality: $metabox.data('cardinality'),
        candidates,
        connections
      });

      window.O2OAdmin.boxes[ctype.o2o_type] = {
        candidates,
        connections
      };
    });

    $(document).trigger('gEditorial:Module:Loaded', [module, app]);
  });
}(jQuery, gEditorial, 'o2obox', 'admin'));
