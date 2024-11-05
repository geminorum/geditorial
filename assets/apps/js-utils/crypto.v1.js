
/**
 * Generates random alphanumeric characters.
 * Using `Math.random()` and `String.fromCharCode()`
 *
 * Example usage: `generateRandomString(8)` // Output: "3uYh6EaF"
 * @source https://rswpthemes.com/how-to-generate-random-alphanumeric-strings-in-javascript/
 *
 * @param {Number} length
 * @returns {string}
 */
const generateRandomString = (length) => {
  let result = '';
  const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  const charactersLength = characters.length;
  for (let i = 0; i < length; i++) {
    result += characters.charAt(Math.floor(Math.random() * charactersLength));
  }
  return result;
};

/**
 * Generates cryptographically strong random values.
 * Utilizing `Crypto.getRandomValues()` for Enhanced Security
 *
 * For applications requiring higher security, such as password generation,
 * we can utilize the Crypto object’s getRandomValues() method, which provides
 * cryptographically strong random values.
 *
 * Example usage: `generateSecureRandomString(10)` // Output: "a5Tzhb1JgF"
 * @source https://rswpthemes.com/how-to-generate-random-alphanumeric-strings-in-javascript/
 *
 * @param {Number} length
 * @returns {string}
 */
const generateSecureRandomString = (length) => {
  let result = '';
  const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  const charactersLength = characters.length;
  const randomValues = new Uint32Array(length);
  window.crypto.getRandomValues(randomValues);
  for (let i = 0; i < length; i++) {
    result += characters.charAt(randomValues[i] % charactersLength);
  }
  return result;
};

/**
 * Generates all combinations of a string using recursion.
 * @source https://rswpthemes.com/how-to-generate-all-combinations-of-a-string-in-javascript/
 *
 * @param {String} str
 * @returns {Array}
 */
const getAllCombinations = (str) => {
  const results = [];

  const generateCombinations = (current, index) => {
    if (index === str.length) {
      results.push(current);
      return;
    }

    generateCombinations(current + str[index], index + 1);
    generateCombinations(current, index + 1);
  };

  generateCombinations('', 0);

  return results;
};

/**
 * Generates all combinations of a string is through iteration.
 * @source https://rswpthemes.com/how-to-generate-all-combinations-of-a-string-in-javascript/
 *
 * @param {String} str
 * @returns {Array}
 */
const getAllCombinationsIterative = (str) => {
  const results = [];

  for (let i = 0; i < Math.pow(2, str.length); i++) {
    let combination = '';

    for (let j = 0; j < str.length; j++) {
      if ((i & (1 << j)) > 0) {
        combination += str[j];
      }
    }

    results.push(combination);
  }

  return results;
};

export {
  generateRandomString,
  generateSecureRandomString,
  getAllCombinations,
  getAllCombinationsIterative
};
