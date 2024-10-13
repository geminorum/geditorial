
/**
 * Escape HTML, but don't double escape any existing decimal,
 * hex or named (xml only) entities.
 * @source https://gist.github.com/mrdaniellewis/3808576
 *
 * @param {String} input
 * @returns {String}
 */
const escaped = (input) => {
  return input
    .replace(/&(?!(?:#\d+|#x[\da-f]+|lt|gt|quot|apos|amp);)/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
};

/**
 * Escapes CSS strings.
 * Based on http://mathiasbynens.be/notes/css-escapes
 * @source https://gist.github.com/mrdaniellewis/4263270
 *
 * @param {String} input
 * @returns {String}
 */
const cssEscape = (input) => {
  return input
    .replace(/[!"#$%&'()*+,./;<=>?@[\\\]^`{|} ]/g, '\\$&') // Escape other special characters
    .replace(/^(\d)/, '\\3$1 ') // Escape leading digits
    .replace(/^_/, '\\_') // Escape leading underscore for IE6
    .replace(/^-[-\d]/, '\\$&') // Escape leading hypen
    .replace(/:/g, '\\3A ') // IE < 8 doesn't like \:
    .replace(/[\t\n\v\f\r]/g, function (m) {
      return '\\' + (+m.charCodeAt(0)).toString(16) + ' ';
    });
};

export {
  escaped,
  cssEscape
};
