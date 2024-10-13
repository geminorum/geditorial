/* eslint no-unused-vars: off */

/**
 * Deep diff between two object, using lodash
 *
 * This code is licensed under the terms of the MIT license
 * @source https://gist.github.com/Yimiprod/7ee176597fef230d1451
 *
 * @param  {Object} object Object compared
 * @param  {Object} base   Object to compare with
 * @return {Object}        Return a new object who represent the diff
 */
const deepDiffObjOLD = (_, object, base) => {
  function changes (object, base) {
    return _.transform(object, function (result, value, key) {
      if (!_.isEqual(value, base[key])) {
        result[key] = (_.isObject(value) && _.isObject(base[key])) ? changes(value, base[key]) : value;
      }
    });
  }

  return changes(object, base);
};

/**
 * Deep diff between two object-likes
 * @source https://gist.github.com/Yimiprod/7ee176597fef230d1451?permalink_comment_id=3415430#gistcomment-3415430
 *
 * @param  {Object} fromObject the original object
 * @param  {Object} toObject   the updated object
 * @return {Object}            a new object which represents the diff
 */
const deepDiff = (_, fromObject, toObject) => {
  const changes = {};

  const buildPath = (path, obj, key) =>
    _.isUndefined(path) ? key : `${path}.${key}`;

  const walk = (fromObject, toObject, path) => {
    for (const key of _.keys(fromObject)) {
      const currentPath = buildPath(path, fromObject, key);
      if (!_.has(toObject, key)) {
        changes[currentPath] = { from: _.get(fromObject, key) };
      }
    }

    for (const [key, to] of _.entries(toObject)) {
      const currentPath = buildPath(path, toObject, key);
      if (!_.has(fromObject, key)) {
        changes[currentPath] = { to };
      } else {
        const from = _.get(fromObject, key);
        if (!_.isEqual(from, to)) {
          if (_.isObjectLike(to) && _.isObjectLike(from)) {
            walk(from, to, currentPath);
          } else {
            changes[currentPath] = { from, to };
          }
        }
      }
    }
  };

  walk(fromObject, toObject);

  return changes;
};

// mom I'm on lodash
// _.mixin({deepDiff});

export {
  deepDiff,
  deepDiffObjOLD
};
