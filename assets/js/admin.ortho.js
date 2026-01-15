/* global QTags, Virastar */

(function ($, p, module) {
  if (typeof p === 'undefined') return;

  const settings = $.extend({}, {
    virastar_on_paste: false
  }, p[module].settings);

  const types = {
    text: '#titlewrap input, input#attachment_alt, input#tag-name, #edittag input#name, [data-' + module + '=\'text\']',
    markdown: '[data-' + module + '=\'markdown\']',
    html: 'textarea#excerpt:not(.wp-editor-area), textarea#attachment_caption, textarea#tag-description, #edittag textarea#description, [data-' + module + '=\'html\']'
  };

  // TODO: extract validations into a Service: `InputValidations`
  // TODO: insert current datetime into input with format via data attribute
  // TODO: couple with: https://github.com/validatorjs/validator.js
  const inputs = {
    number: '[data-' + module + '=\'number\']',
    alphabet: '[data-' + module + '=\'alphabet\']', // example: warehouse partials values (UpperCase)
    slug: '[data-' + module + '=\'slug\']', // example: warehouse partials names (LowerCase)
    hook: '[data-' + module + '=\'hook\']',
    // path: '[data-' + module + '=\'path\']', // TODO!
    // pattern: '[data-' + module + '=\'pattern\']', // TODO!
    // url: '[data-' + module + '=\'url\']', // TODO!: support relative paths
    identity: '[data-' + module + '=\'identity\']',
    phone: '[data-' + module + '=\'phone\']',
    isbn: '[data-' + module + '=\'isbn\']',
    vin: '[data-' + module + '=\'vin\']',
    plate: '[data-' + module + '=\'plate\']',
    iban: '[data-' + module + '=\'iban\']',
    bankcard: '[data-' + module + '=\'bankcard\']',
    year: '[data-' + module + '=\'year\']',
    date: '[data-' + module + '=\'date\']',
    datetime: '[data-' + module + '=\'datetime\']',
    distance: '[data-' + module + '=\'distance\']',
    duration: '[data-' + module + '=\'duration\']',
    area: '[data-' + module + '=\'area\']'
    // latlng: '[data-' + module + '=\'latlng\']',
    // code: '[data-' + module + '=\'code\']',
    // color: '[data-' + module + '=\'color\']',
    // currency: '[data-' + module + '=\'currency\']'
  };

  const strings = $.extend({}, {
    button_virastar: '<span class="dashicons dashicons-text"></span>', // TODO: change to `virastar` icon
    button_virastar_title: 'Apply Virastar!',
    qtag_virastar: 'Virastar!',
    qtag_virastar_title: 'Apply Virastar!',
    qtag_swapquotes: 'Swap Quotes',
    qtag_swapquotes_title: 'Swap Not-Correct Quotes',
    qtag_mswordnotes: 'Word Footnotes',
    qtag_mswordnotes_title: 'MS Word Footnotes to WordPress [ref]',
    qtag_download: 'Download',
    qtag_download_title: 'Download text as markdown',
    qtag_nbsp: 'nbsp',
    qtag_nbsp_title: 'Non-Breaking SPace'
  }, p[module].strings);

  const doButton = '<a href="#" class="do-' + module + '" title="' + strings.button_virastar_title + '" tabindex="-1">' + strings.button_virastar + '</a>';
  // const doWrap = '<span class="' + module + '-input-wrap"></span>';

  const options = p[module].virastar || {};

  const virastar = {

    text: new Virastar($.extend({}, options, {
      preserve_HTML: false,
      preserve_URIs: false,
      preserve_frontmatter: false
    })),

    markdown: new Virastar($.extend({}, options, {
      // fix_dashes: false,
      // cleanup_spacing: false,
      // cleanup_begin_and_end: false,
      preserve_frontmatter: false,
      skip_markdown_ordered_lists_numbers_conversion: true
    })),

    html: new Virastar($.extend({}, options, {
      // cleanup_spacing: false,
      preserve_frontmatter: false,
      preserve_brackets: true,
      preserve_braces: true
    }))
  };

  function doFootnotes (content) {
    const footnotes = {};
    content = content.replace(/<a[^h]*(?=href="[^"]*#_(?:ftn|edn|etc)ref([0-9]+)")[^>]*>\[([0-9]+)\]<\/a>(.*)/g, function (m, p1, p2, p3) {
      footnotes[p1] = p3.replace(/^\s*|\s*$/gm, '').trim();
      return '';
    });

    return content.replace(/<a[^h]*(?=href="[^"]*#_(?:ftn|edn|etc)([0-9]+)")[^>]*>(?:<\w+>)*\[([0-9]+)\](?:<\/\w+>)*<\/a>/g, function (m, p1, p2, p3, p4) {
      return '[ref]' + footnotes[p1].replace(/\r\n|\r|\n/g, ' ').trim() + '[/ref]';
    });
  }

  function swapQuotes (content) {
    return content
      .replace(/(»)(.+?)(«)/g, '«$2»')
      .replace(/(”)(.+?)(“)/g, '“$2”');
  }

  /**
   * Determine whether the given `input` is a number.
   * @source https://futurestud.io/tutorials/javascript-check-if-a-string-is-a-number
   *
   * @param {String} input
   *
   * @returns {Boolean}
   */
  function isNumericString (input) {
    return typeof input === 'string' && !Number.isNaN(input);
  }

  /**
   * Removes the given string from the beginning of a string.
   * @source https://stackoverflow.com/a/70518537
   *
   * @param {String} input
   * @param {String} prefix
   * @returns {String}
   */
  function removePrefix (input, prefix) {
    return input.startsWith(prefix) ? input.slice(prefix.length) : input;
  }

  function sanitizeDashes (input) {
    return input
      // Converts `kashida` between numbers to dash
      // .replace(/([0-9۰-۹]+)ـ+([0-9۰-۹]+)/g, '$1-$2')
      .replace(/[\u0640—–]+/g, '-')
    ;
  }

  function flipByDash (input) {
    return input
      .split('-')
      .reverse()
      .join('-')
    ;
  }

  // function toPersian (n) {
  //   const p = '۰'.charCodeAt(0);
  //   return n.toString().replace(/\d+/g, function (m) {
  //     return m.split('').map(function (n) {
  //       return String.fromCharCode(p + parseInt(n));
  //     }).join('');
  //   });
  // }

  function toEnglish (n) {
    return n.toString().replace(/[۱۲۳۴۵۶۷۸۹۰]+/g, function (m) {
      return m.split('').map(function (n) {
        return n.charCodeAt(0) % 1776;
      }).join('');
    });
  }

  // @REF: http://codepen.io/geminorum/pen/Ndzdqw
  function downloadText (filename, text) {
    const element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
  }

  // @REF: https://gist.github.com/mhf-ir/3b6d67e73f04874eea6baece3e43a5c0
  function identityNumber (value) {
    if (typeof value === 'undefined' || !value) {
      return false;
    }

    if (!isNumericString(value)) {
      return false;
    }

    if (value.trim().length !== 10) {
      return false;
    }

    const check = parseInt(value[9], 10);
    let sum = 0;
    for (let i = 0; i < 9; i += 1) {
      sum += parseInt(value[i], 10) * (10 - i);
    }
    sum %= 11;
    return (sum < 2 && check === sum) || (sum >= 2 && check + sum === 11);
  }

  function iso7064Mod9710 (value) {
    let remainder = value;
    let block;

    while (remainder.length > 2) {
      block = remainder.slice(0, 9);
      remainder = parseInt(block, 10) % 97 + remainder.slice(block.length);
    }

    return parseInt(remainder, 10) % 97;
  }

  function validatePhone (value) {
    if (typeof value === 'undefined' || !value) {
      return false;
    }

    // @REF: https://www.abstractapi.com/guides/validate-phone-number-javascript
    // const pattern = /^\(?(\d{3})\)?[- ]?(\d{3})[- ]?(\d{4})$/;

    // @REF: https://www.w3resource.com/javascript/form/phone-no-validation.php
    // const pattern = /^\+?([0-9]{2})\)?[-. ]?([0-9]{4})[-. ]?([0-9]{4})$/;

    // @REF: https://rgxdb.com/r/4MEBA3DO
    const pattern = /^[+]?(?=(?:[^\dx]*\d){7})(?:\(\d+(?:\.\d+)?\)|\d+(?:\.\d+)?)(?:[ -]?(?:\(\d+(?:\.\d+)?\)|\d+(?:\.\d+)?))*(?:[ ]?(?:x|ext)\.?[ ]?\d{1,5})?$/;

    return pattern.test(value);
  }

  function validateVIN (input) {
    if (!input) return false;
    // input = toEnglish(input.toUpperCase());
    if (input.length !== 17) return false;
    const vinPattern = /[a-zA-Z0-9]{9}[a-zA-Z0-9-]{2}[0-9]{6}/; // https://www.regextester.com/100058
    if (!vinPattern.test(input)) return false;
    return true;
  }

  function validatePlate (input) {
    if (!input) return false;
    // input = toEnglish(input.toUpperCase());
    return true;
  }

  // @REF: https://www.oreilly.com/library/view/regular-expressions-cookbook/9781449327453/ch04s13.html
  function validateISBN (value) {
    if (typeof value === 'undefined' || !value) {
      return false;
    }

    // checks for ISBN-10 or ISBN-13 format
    const pattern = /^(?:ISBN(?:-1[03])?:? )?(?=[0-9X]{10}$|(?=(?:[0-9]+[- ]){3})[- 0-9X]{13}$|97[89][0-9]{10}$|(?=(?:[0-9]+[- ]){4})[- 0-9]{17}$)(?:97[89][- ]?)?[0-9]{1,5}[- ]?[0-9]+[- ]?[0-9]+[- ]?[0-9X]$/;
    // const pattern = /^(?:ISBN(?:-13)?:?\ )?(?=[0-9]{13}$|(?=(?:[0-9]+[-\ ]){4})[-\ 0-9]{17}$)97[89][-\ ]?[0-9]{1,5}[-\ ]?[0-9]+[-\ ]?[0-9]+[-\ ]?[0-9]$/;

    if (pattern.test(value)) {
      // remove non ISBN digits, then split into an array
      const chars = value.replace(/[- ]|^ISBN(?:-1[03])?:?/g, '').split('');

      // remove the final ISBN digit from `chars`, and assign it to `last`
      const last = chars.pop();
      let sum = 0;
      let check, i;

      if (chars.length === 9) {
        // compute the ISBN-10 check digit
        chars.reverse();

        for (i = 0; i < chars.length; i++) {
          sum += (i + 2) * parseInt(chars[i], 10);
        }

        check = 11 - (sum % 11);

        if (check === 10) {
          check = 'X';
        } else if (check === 11) {
          check = '0';
        }
      } else {
        // compute the ISBN-13 check digit
        for (i = 0; i < chars.length; i++) {
          sum += (i % 2 * 2 + 1) * parseInt(chars[i], 10);
        }

        check = 10 - (sum % 10);

        if (check === 10) {
          check = '0';
        }
      }

      return check === parseInt(last);
    }

    return false;
  }

  // @REF: https://gist.github.com/mhf-ir/c17374fae395a57c9f8e5fe7a92bbf23
  function validateIBAN (value) {
    if (typeof value === 'undefined' || !value) {
      return false;
    }

    if (value.trim().length !== 26) {
      return false;
    }

    if (!/IR[0-9]{24}/.test(value)) {
      return false;
    }

    let check = value.substr(4);
    const d1 = value.charCodeAt(0) - 65 + 10;
    const d2 = value.charCodeAt(1) - 65 + 10;
    check += d1.toString() + d2.toString() + value.substr(2, 2);

    return iso7064Mod9710(check) === 1;
  }

  // @source https://github.com/sunnywebco/bankcardcheckiran
  // @REF: https://vrgl.ir/QaQIP
  function validateCard (value) {
    if (typeof value === 'undefined' || !value) return false;
    const length = value.trim().length;
    if (length < 16 || parseInt(value.substr(1, 10), 10) === 0 || parseInt(value.substr(10, 6), 10) === 0) return false;
    // const c = parseInt(value.substr(15, 1), 10);
    let s = 0;
    let k;
    let d;

    for (let i = 0; i < 16; i++) {
      k = (i % 2 === 0) ? 2 : 1;
      d = parseInt(value.substr(i, 1), 10) * k;
      s += (d > 9) ? d - 9 : d;
    }

    return ((s % 10) === 0);
  }

  const inputCallbacks = {

    number: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text'); // NOTE: possible type: `number`
      } catch (e) {}
      $el.on('change', function () {
        $el.val(toEnglish(sanitizeDashes($el.val())).replace(/[^\d.-]/g, '').trim());
      });
    },

    alphabet: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text');
      } catch (e) {}
      $el.on('change', function () {
        $el.val($el.val().replace(/[^a-zA-Z]/gi, '').trim().toUpperCase());
      });
    },

    slug: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text');
      } catch (e) {}
      $el.on('change', function () {
        $el.val(toEnglish(sanitizeDashes($el.val().trim().replace(/[\s-_]+/gi, '-'))).replace(/[^a-zA-Z0-9-]/gi, '').trim().toLowerCase());
      });
    },

    hook: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text');
      } catch (e) {}
      $el.on('change', function () {
        $el.val(toEnglish(sanitizeDashes($el.val().trim().replace(/[\s-]+/gi, '_'))).replace(/[^a-zA-Z0-9_]/gi, '').trim().toLowerCase());
      });
    },

    identity: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text');
      } catch (e) {}
      $el.on('change', function () {
        const val = toEnglish(sanitizeDashes($el.val())).replace(/[^\d.-]/g, '').trim();
        $el.val(val);
        if (identityNumber(val)) {
          $el.addClass('ortho-is-valid').removeClass('ortho-not-valid');
        } else {
          $el.addClass('ortho-not-valid').removeClass('ortho-is-valid');
        }
      });
    },

    phone: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text'); // NOTE: possible type: `tel`
      } catch (e) {}
      $el.on('change', function () {
        const val = removePrefix(toEnglish(sanitizeDashes($el.val())), 'tel:').replace(/[^\d+]/g, '').trim();
        $el.val(val);
        if (validatePhone(val)) {
          $el.addClass('ortho-is-valid').removeClass('ortho-not-valid');
        } else {
          $el.addClass('ortho-not-valid').removeClass('ortho-is-valid');
        }
      });
    },

    isbn: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text'); // NOTE: possible type: `number`
      } catch (e) {}
      $el.on('change', function () {
        const val = toEnglish(sanitizeDashes($el.val())).replace(/[^\d.-]/g, '').trim();
        $el.val(val);
        console.log(flipByDash(val));
        if (validateISBN(val)) {
          $el.addClass('ortho-is-valid').removeClass('ortho-not-valid');
        } else if (validateISBN(flipByDash(val))) {
          $el.val(flipByDash(val));
          $el.addClass('ortho-is-valid').removeClass('ortho-not-valid');
        } else {
          $el.addClass('ortho-not-valid').removeClass('ortho-is-valid');
        }
      });
    },

    vin: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text');
      } catch (e) {}
      $el.on('change', function () {
        const val = toEnglish(sanitizeDashes($el.val())).toUpperCase().replace(/[^A-Z\d.-]/g, '').trim();
        $el.val(val);
        if (validateVIN(val)) {
          $el.addClass('ortho-is-valid').removeClass('ortho-not-valid');
        } else {
          $el.addClass('ortho-not-valid').removeClass('ortho-is-valid');
        }
      });
    },

    plate: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text');
      } catch (e) {}
      $el.on('change', function () {
        const val = toEnglish(sanitizeDashes($el.val())).toUpperCase().replace(/[^A-Z\d.-]/g, '').trim();
        $el.val(val);
        if (validatePlate(val)) {
          $el.addClass('ortho-is-valid').removeClass('ortho-not-valid');
        } else {
          $el.addClass('ortho-not-valid').removeClass('ortho-is-valid');
        }
      });
    },

    iban: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text'); // NOTE: possible type: `number`
      } catch (e) {}
      $el.on('change', function () {
        const val = toEnglish(sanitizeDashes($el.val())).replace(/IR[^\d.-]/g, '').trim();
        $el.val(val);
        if (validateIBAN(val)) {
          $el.addClass('ortho-is-valid').removeClass('ortho-not-valid');
        } else {
          $el.addClass('ortho-not-valid').removeClass('ortho-is-valid');
        }
      });
    },

    bankcard: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text'); // NOTE: possible type: `number`
      } catch (e) {}
      $el.on('change', function () {
        const val = toEnglish(sanitizeDashes($el.val())).replace(/[^\d.-]/g, '').trim();
        $el.val(val);
        if (validateCard(val)) {
          $el.addClass('ortho-is-valid').removeClass('ortho-not-valid');
        } else {
          $el.addClass('ortho-not-valid').removeClass('ortho-is-valid');
        }
      });
    },

    year: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text'); // NOTE: possible type: `year`
      } catch (e) {}
      $el.on('change', function () {
        $el.val(toEnglish(sanitizeDashes($el.val())).replace(/[^\d.\-/\\]/g, '').trim());
        // TODO: check for pattern/validate year in Persian
      });
    },

    date: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text'); // NOTE: possible type: `date`
      } catch (e) {}
      $el.on('change', function () {
        $el.val(toEnglish(sanitizeDashes($el.val())).replace(/[^\d.\-/\\]/g, '').trim());
        // TODO: check for pattern/validate date in Persian
      });
    },

    datetime: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text'); // NOTE: possible type: `date`
      } catch (e) {}
      $el.on('change', function () {
        $el.val(toEnglish(sanitizeDashes($el.val())).replace(/[^\d.\-/\\: ]/g, '').trim());
        // TODO: check for pattern/validate datetime in Persian
      });
    },

    // TODO: convert to the target unit
    // @SEE https://github.com/lvivier/meters/blob/master/index.js
    distance: function () {
      const $el = $(this);
      // TODO: get data before change type not to lose the unit
      try {
        $el.prop('type', 'text'); // NOTE: possible type: `number`
      } catch (e) {}
      $el.on('change', function () {
        $el.val(toEnglish(sanitizeDashes($el.val())).replace(/[^\d.\-/\\ ]/g, '').trim());
        // TODO: check for pattern/validate distance in Persian
      });
    },

    duration: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text'); // NOTE: possible type: `number`
      } catch (e) {}
      $el.on('change', function () {
        $el.val(toEnglish(sanitizeDashes($el.val())).replace(/[^\d.\-/\\: ]/g, '').trim());
        // TODO: check for pattern/validate duration in Persian
      });
    },

    area: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text'); // NOTE: possible type: `number`
      } catch (e) {}
      $el.on('change', function () {
        $el.val(toEnglish(sanitizeDashes($el.val())).replace(/[^\d.\-/\\ ]/g, '').trim());
        // TODO: check for pattern/validate duration in Persian
      });
    }

    // code: function () {},
    // color: function () {},
    // currency: function () {} // @SEE: https://github.com/habibpour/rial.js
  };

  // Maps `quicktag` buttons to targeted editor id
  const quickButtons = {
    nbsp: '',
    virastar: '',
    swapquotes: 'content',
    mswordnotes: 'content',
    download: 'content'
  };

  const quickCallbacks = {

    nbsp: function (e, c, ed) {
      QTags.insertContent('\n\n' + '&nbsp;' + '\n\n');
    },

    virastar: function (e, c, ed) {
      const s = c.value.substring(c.selectionStart, c.selectionEnd);
      if (s !== '') {
        QTags.insertContent(virastar.html.cleanup(s));
      } else {
        $(c).val(virastar.html.cleanup($(c).val()));
      }
    },

    swapquotes: function (e, c, ed) {
      $(c).val(swapQuotes($(c).val()));
    },

    mswordnotes: function (e, c, ed) {
      $(c).val(doFootnotes($(c).val()));
    },

    download: function (e, c, ed) {
      let filename = 'Untitled';
      let metadata = '';

      if ($('#title').length && $('#title').val()) {
        filename = $('#title').val().trim();
      } else if ($('#tag-name').length && $('#tag-name').val()) {
        filename = $('#tag-name').val().trim();
      } else if ($('#name').length && $('#name').val()) {
        filename = $('#name').val().trim();
      } else if ($('#post_ID').length) {
        filename = 'Untitled-' + $('#post_ID').val();
      }

      $('input[data-meta-title]').each(function (i) {
        const text = $(this).val();
        if (text) metadata = metadata + $(this).data('meta-title') + ': ' + text + '\n';
      });

      // `Frontmatter`
      if (metadata) metadata = '---\n' + metadata + '---\n\n';

      downloadText(filename + '.md', metadata + '## ' + filename + '\n' + $(c).val());
    }
  };

  $(function () {
    for (const type in types) {
      $(types[type]).each(function () {
        $(this).data(module, type)
          .addClass('target-' + module)
          .add($(doButton))
          .wrapAll('<span class="' + module + '-input-wrap ' + module + '-input-' + type + '-wrap"></span>');
      });

      $('a.do-' + module).on('click', function (event) {
        event.preventDefault();
        const target = $(this).closest('.' + module + '-input-wrap').find('.target-' + module);
        target.val(virastar[target.data(module)].cleanup(target.val()));
      });

      if (settings.virastar_on_paste) {
        $('.target-' + module).on('paste', function () {
          const el = this;
          setTimeout(function () {
            const target = $(el);
            target.val(virastar[target.data(module)].cleanup(target.val()));
          }, 100);
        });
      }
    }

    for (const input in inputs) {
      $(inputs[input]).each(function () {
        inputCallbacks[input].call(this);
      });
    }

    if (typeof QTags !== 'undefined') {
      for (const button in quickButtons) {
        QTags.addButton(
          button,
          strings['qtag_' + button],
          quickCallbacks[button],
          '',
          '',
          strings['qtag_' + button + '_title'],
          0,
          quickButtons[button]
        );
      }
    }

    try {
      // Gives back focus to the title input.
      document.post.title.focus();
    } catch (e) {}

    $(document).trigger('gEditorialReady', [module, null]);
  });
}(jQuery, gEditorial, 'ortho'));
