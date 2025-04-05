/**
 * NOTE: Note that `Lodash` 5 will drop support for `omit`
 * @source https://stackoverflow.com/a/40339196
 *
 * https://github.com/lodash/lodash/issues/2930#issuecomment-272298477
 * https://dustinpfister.github.io/2019/08/19/lodash_omit/
 *
 * https://codeburst.io/use-es2015-object-rest-operator-to-omit-properties-38a3ecffe90
 * https://github.com/airbnb/javascript/blob/master/README.md#objects--rest-spread
 * ```
 * const list = { a: 1, b: 2, c: 3 };
 * const { a, ...omitted } = list; // omitted => { b: 2, c: 3 }
 * ```
 */

/**
 * @source https://stackoverflow.com/a/40339196
 * @param {Object} obj
 * @param {Bool} predicate
 * @returns {Object}
 */
const transform = (obj, predicate) => {
  return Object.keys(obj)
    .reduce((memo, key) => {
      if (predicate(obj[key], key)) {
        memo[key] = obj[key];
      }

      return memo;
    }, {});
};

const omit = (obj, items) => transform(obj, (value, key) => !items.includes(key));
const pick = (obj, items) => transform(obj, (value, key) => items.includes(key));

const omitDeep = (list, items) => list.map((obj) => omit(obj, items));
const pickDeep = (list, items) => list.map((obj) => pick(obj, items));

export {
  omit,
  pick,
  omitDeep,
  pickDeep,
  transform
};
