
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

/**
 * Add a stylesheet rule to the document (it may be better practice
 * to dynamically change classes, so style information can be kept in
 * genuine stylesheets and avoid adding extra elements to the DOM).
 * Note that an array is needed for declarations and rules since ECMAScript does
 * not guarantee a predictable object iteration order, and since CSS is
 * order-dependent.
 * @source https://developer.mozilla.org/en-US/docs/Web/API/CSSStyleSheet/insertRule
 *
 * @param {Array} rules Accepts an array of JSON-encoded declarations
 * @example ```js
addStylesheetRules([
  ['h2', // Also accepts a second argument as an array of arrays instead
    ['color', 'red'],
    ['background-color', 'green', true] // 'true' for !important rules
  ],
  ['.myClass',
    ['background-color', 'yellow']
  ]
]);```
*/
function addStylesheetRules (rules) {
  const styleEl = document.createElement('style');

  // Append <style> element to <head>
  document.head.appendChild(styleEl);

  // Grab style element's sheet
  const styleSheet = styleEl.sheet;

  for (let rule of rules) {
    let i = 1;
    let propStr = '';
    const selector = rule[0];

    // If the second argument of a rule is an array of arrays, correct our variables.
    if (Array.isArray(rule[1][0])) {
      rule = rule[1];
      i = 0;
    }

    for (; i < rule.length; i++) {
      const prop = rule[i];
      propStr += `${prop[0]}: ${prop[1]}${prop[2] ? ' !important' : ''};\n`;
    }

    // Insert CSS Rule
    styleSheet.insertRule(
      `${selector}{${propStr}}`,
      styleSheet.cssRules.length
    );
  }
}

export {
  addStylesheetRules,
  escaped,
  cssEscape
};
