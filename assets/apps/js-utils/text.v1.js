
/**
 * Replaces multiple spaces with single space.
 *
 * @source https://www.geeksforgeeks.org/how-to-replace-multiple-spaces-with-single-space-in-javascript/
 *
 * @param {String} input
 * @returns {String}
 */
const toSingleSpace = (input) => {
  return input.trim().split(/[\s,\t,\n]+/).join(' ');
};

/**
 * Splits a string by lines.
 * @param {String} input
 * @returns {Array}
 */
const toMultipleLines = (input) => {
  return input.trim().split(/[\r\n]+/);
};

/**
 * Replaces `\n` with `<br />`.
 *
 * @source https://dev.to/cassidoo/make-line-breaks-work-when-you-render-text-in-a-react-or-vue-component-4m0n
 *
 * @param {String} input
 * @returns {String}
 */
const toHTLMLLineBreaks = (input) => {
  return input.replace(/\n/g, '<br />');
};

/**
 * Adds a space between each character in a string.
 * @sourece https://gitlab.com/aldgagnon/get-spacey/
 *
 * @param {String} input
 * @returns {String}
 */
const spacey = (input) => {
  return input
    .replace(/\s/g, '')
    .replace(/./g, '$& ')
    .trim();
  // .toUpperCase();
};

/**
 * Converts to any case
 * @source https://www.30secondsofcode.org/js/s/string-case-conversion/
 * @param {String} str
 * @param {String} toCase
 * @returns {String}
 */
const convertCase = (str, toCase = 'camel') => {
  if (!str) return '';

  const delimiter =
    toCase === 'snake'
      ? '_'
      : toCase === 'kebab'
        ? '-'
        : ['title', 'sentence'].includes(toCase)
            ? ' '
            : '';

  const transform = ['camel', 'pascal'].includes(toCase)
    ? x => x.slice(0, 1).toUpperCase() + x.slice(1).toLowerCase()
    : ['snake', 'kebab'].includes(toCase)
        ? x => x.toLowerCase()
        : toCase === 'title'
          ? x => x.slice(0, 1).toUpperCase() + x.slice(1)
          : x => x;

  const finalTransform =
    toCase === 'camel'
      ? x => x.slice(0, 1).toLowerCase() + x.slice(1)
      : toCase === 'sentence'
        ? x => x.slice(0, 1).toUpperCase() + x.slice(1)
        : x => x;

  const words = str.match(
    /[A-Z]{2,}(?=[A-Z][a-z]+[0-9]*|\b)|[A-Z]?[a-z]+[0-9]*|[A-Z]|[0-9]+/g
  );

  return finalTransform(words.map(transform).join(delimiter));
};

export {
  convertCase,
  spacey,
  toHTLMLLineBreaks,
  toMultipleLines,
  toSingleSpace
};
