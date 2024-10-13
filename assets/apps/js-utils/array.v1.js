/* eslint no-unused-vars: off */

const prepString = (input) => {
  input = input.map(Function.prototype.call, String.prototype.trim);
  input = input.filter(Boolean);
  input = input.filter(() => { return true; });
  // input = input.filter(onlyUnique);
  input = [...new Set(input)];

  return input;
};

// https://stackoverflow.com/a/14438954
const onlyUnique = (value, index, array) => {
  return array.indexOf(value) === index;
};

/**
 * Plucks any number of values for each object
 * @source https://www.30secondsofcode.org/js/s/pluck-values-from-object-array/
 * @example `pluck(simpsons, 'name', 'age');`: `[['Lisa', 8], ['Homer', 36], ['Marge', 34], ['Bart', 10]]`
 * @example `pluck(simpsons, 'age');`: `[8, 36, 34, 10]`
 *
 * @param {Array} array
 * @param {Array} keys
 * @returns {Array} values
 */
const pluck = (array, ...keys) =>
  keys.length > 1
    ? array.map(i => keys.map(k => i[k]))
    : array.map(i => i[keys[0]]);

/**
 * Counts the occurrences of each value in an array.
 * @source https://www.30secondsofcode.org/js/s/count-grouped-elements/
 * @example `frequencies(['a', 'b', 'a', 'c', 'a', 'a', 'b']);`: `{ a: 4, b: 2, c: 1 }`
 * @example: `frequencies([...'ball']);`: `{ b: 1, a: 1, l: 2 }`
 *
 * @param {Array} array
 * @returns {Object} occurrences
 */
const frequencies = array =>
  array.reduce((a, v) => {
    a[v] = (a[v] ?? 0) + 1;
    return a;
  }, {});

/**
 * Groups the elements of an array based on a function.
 * @source https://www.30secondsofcode.org/js/s/count-grouped-elements/
 * @example `countBy([6.1, 4.2, 6.3], Math.floor);`: `{4: 1, 6: 2}`
 * @example `countBy(['one', 'two', 'three'], 'length');`: `{3: 2, 5: 1}`
 * @example `countBy([{ count: 5 }, { count: 10 }, { count: 5 }], x => x.count);`: `{5: 2, 10: 1}`
 *
 * @param {Array} array
 * @param {Function} fn
 * @returns {Object} groups
 */
const countBy = (array, fn) =>
  array
    .map(typeof fn === 'function' ? fn : val => val[fn])
    .reduce((acc, val) => {
      acc[val] = (acc[val] || 0) + 1;
      return acc;
    }, {});

/**
 * Gets all unique values in an array.
 * @source https://www.30secondsofcode.org/js/s/unique-values-in-array-remove-duplicates/
 * @example `uniqueElements([1, 2, 2, 3, 4, 4, 5]);`: `[1, 2, 3, 4, 5]`
 *
 * @param {Array} arr
 * @returns
 */
const uniqueElements = arr => [...new Set(arr)];

/**
 * Checks if an array contains duplicates.
 * hasDuplicates([1, 2, 2, 3, 4, 4, 5]); // true
 * hasDuplicates([1, 2, 3, 4, 5]); // false
 *
 * @param {Array} arr
 * @returns
 */
const hasDuplicates = arr => arr.length !== new Set(arr).size;

/**
 * Checks if all the values of an array are distinct.
 * allDistinct([1, 2, 2, 3, 4, 4, 5]); // false
 * allDistinct([1, 2, 3, 4, 5]); // true
 *
 * @param {Array} arr
 * @returns
 */
const allDistinct = arr =>
  arr.length === new Set(arr).size;

/**
 * Removes array values that appear more than once.
 * removeNonUnique([1, 2, 2, 3, 4, 4, 5]); // [1, 3, 5]
 *
 * @param {*} arr
 * @returns
 */
const removeNonUnique = arr =>
  [...new Set(arr)].filter(i => arr.indexOf(i) === arr.lastIndexOf(i));

// removeUnique([1, 2, 2, 3, 4, 4, 5]); // [2, 4]
const removeUnique = arr =>
  [...new Set(arr)].filter(i => arr.indexOf(i) !== arr.lastIndexOf(i));

const uniqueElementsBy = (arr, fn) =>
  arr.reduce((acc, v) => {
    if (!acc.some(x => fn(v, x))) acc.push(v);
    return acc;
  }, []);

const hasDuplicatesBy = (arr, fn) =>
  arr.length !== new Set(arr.map(fn)).size;

const removeNonUniqueBy = (arr, fn) =>
  arr.filter((v, i) => arr.every((x, j) => (i === j) === fn(v, x, i, j)));

/**
 * Combines two object arrays based on a key.
 * @source https://www.30secondsofcode.org/js/s/combine-object-arrays/
 *
 * `const x = [ { id: 1, name: 'John' }, { id: 2, name: 'Maria' } ];`
 * `const y = [ { id: 1, age: 28 }, { id: 3, age: 26 }, { age: 3 } ];`
 * `combine(x, y, 'id');`: `[ { id: 1, name: 'John', age: 28 }, { id: 2, name: 'Maria' }, { id: 3, age: 26 } ]`
 *
 * @param {Array} a
 * @param {Array} b
 * @param {String} prop
 * @returns {Array}
 */
const combine = (a, b, prop) =>
  Object.values(
    [...a, ...b].reduce((acc, v) => {
      if (v[prop]) {
        acc[v[prop]] = acc[v[prop]]
          ? { ...acc[v[prop]], ...v }
          : { ...v };
      }
      return acc;
    }, {})
  );

export {
  allDistinct,
  combine,
  countBy,
  frequencies,
  hasDuplicates,
  hasDuplicatesBy,
  pluck,
  prepString,
  removeNonUnique,
  removeNonUniqueBy,
  removeUnique,
  uniqueElements,
  uniqueElementsBy
};
