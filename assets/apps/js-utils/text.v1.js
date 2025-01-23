
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

/**
 * Find all Permutatoins of a String
 * @source https://github.com/fabiankaegy/practice-recursive-functions/blob/master/permutate.js
 *
 * @param {String} inputString
 * @returns {String} permutate
 */
const permutate = (inputString) => {
  if (inputString.length === 1) return [inputString];

  const allPermutations = [];

  inputString.split('').forEach((currentLetter, index) => {
    // get the letters before and after the current index and join them together
    const remainingLetters = inputString.slice(0, index) + inputString.slice(index + 1);

    // get the permutations of the remaining letters
    const permutationsOfRemainingLetters = permutate(remainingLetters);

    permutationsOfRemainingLetters.forEach(subPermutation => {
      // join the sub permutation with the cureent letter and add to all permutations
      allPermutations.push(currentLetter + subPermutation);
    });
  });

  return allPermutations;
};

/**
 * Reverse a String
 * @source https://github.com/fabiankaegy/practice-recursive-functions/blob/master/reverseString.js
 *
 * @param {String} inputString
 * @returns {String} reversed
 */
const reverse = (inputString) => {
  const reverseString = (input, index) => {
    // return the raw input once all letters were moved
    if (index === 0) return input;

    // convert to array with all letters
    input = input.split('');

    // get the letter at the current posotion starting from the back
    const letter = input.splice(index - 1, 1);

    // add that letter to the end of the array
    input.push(letter);

    // run recirsivly while lowering the index by one
    return reverseString(input.join(''), index - 1);
  };

  return reverseString(inputString, inputString.length);
};

export {
  convertCase,
  spacey,
  toHTLMLLineBreaks,
  toMultipleLines,
  toSingleSpace,
  permutate,
  reverse
};
