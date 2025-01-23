
/**
 * String to integer
 * `'12345'`    => `12345`
 * `'12345.67'` => `12345`
 *
 * @param {String} input
 * @returns {Int}
 */
const toInt = (input) => {
  return parseInt(input, 10);
};

/**
 * String to decimal
 * `'12345.67'` => `12345.67`
 * `'12345'`    => `12345`
 *
 * @param {String} input
 * @returns {Int}
 */
const toDecimal = (input) => {
  return Number(input);
};

/**
 * Determine whether the given `input` is a number.
 * @source https://futurestud.io/tutorials/javascript-check-if-a-string-is-a-number
 * @SEE https://bobbyhadz.com/blog/javascript-check-if-character-in-string-is-number
 *
 * @param {String} input
 * @returns {Boolean}
 */
const isNumericString = (input) => {
  return typeof input === 'string' && !Number.isNaN(input);
};

/**
 * Pads a number with leading zeros.
 * @source https://stackoverflow.com/a/10073788
 * @SEE: `String.prototype.padStart()`
 *
 * @param {String} n
 * @param {Int} width
 * @param {String} z
 * @returns {String}
 */
const padWith = (n, width, z) => {
  z = z || '0';
  n = n + '';
  return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
};

// Check if a value is within a range of numbers
// https://stackoverflow.com/a/49724916
const inRange = (x, min, max) => {
  return ((x - min) * (x - max) <= 0);
};

// https://stackoverflow.com/a/6454237
// Check if a value is within a range of numbers
const between = (x, min, max) => {
  return x >= min && x <= max;
};

const formatNumber = (input, locale) => {
  return locale === 'fa-IR' ? toPersian(input) : toEnglish(input);
};

const toPersian = (input) => {
  const p = '۰'.charCodeAt(0);
  return input.toString().replace(/\d+/g, function (m) {
    return m.split('').map(function (d) {
      return String.fromCharCode(p + parseInt(d));
    }).join('');
  });
};

const toEnglish = (input) => {
  return input.toString().replace(/[۱۲۳۴۵۶۷۸۹۰]+/g, function (m) {
    return m.split('').map(function (n) {
      return n.charCodeAt(0) % 1776;
    }).join('');
  });
};

/**
 * @source https://www.npmjs.com/package/iran-basic
 *
 * @param {String} input
 * @returns
 */
const isArabic = (input) => {
  return /[\u0600-\u06FF\u0750-\u077F]/.test(input);
};

/**
 * @source https://www.npmjs.com/package/iran-basic
 *
 * @param {String} input
 * @returns {String}
 */
const parseArabic = (input) => {
  return input.replace(/[٠١٢٣٤٥٦٧٨٩]/g, function (d) {
    return d.charCodeAt(0) - 1632;
  }).replace(/[۰۱۲۳۴۵۶۷۸۹]/g, function (d) {
    return d.charCodeAt(0) - 1776;
  });
};

/**
 * Calculate the Factorial number
 * @source https://github.com/fabiankaegy/practice-recursive-functions/blob/master/factorial.js
 *
 * @param {Int} number
 * @returns {Int} factorial
 */
const factorial = (number) => {
  if (typeof number !== 'number') throw new TypeError('Input must be a number');
  if (number <= 0) throw new TypeError('The input needs to be a positive integer');

  // setup variable that will get updated beacuse of the closure nature in js
  let total = number;

  const calculateFactorial = number => {
    total *= number;

    // exit early and return the final value when the number arrived at 1
    if (number === 1) return total;

    // call yourself recuresivly with the number - 1
    return calculateFactorial(number - 1);
  };

  // initiate the recursion
  return calculateFactorial(number - 1);
};

export {
  toInt,
  toDecimal,
  isArabic,
  parseArabic,
  formatNumber,
  isNumericString,
  padWith,
  inRange,
  between,
  toEnglish,
  toPersian,
  factorial
};
