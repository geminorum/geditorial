
/**
 * Extracts extension from filename string.
 * @source https://stackoverflow.com/a/680982
 *
 * (?:         # begin non-capturing group
 *   \.        #   a dot
 *   (         #   begin capturing group (captures the actual extension)
 *     [^.]+   #     anything except a dot, multiple times
 *   )         #   end capturing group
 * )?          # end non-capturing group, make it optional
 * $           # anchor to the end of the string
 *
 * @param {String} input
 * @returns {String}
 */
const getExt = (input) => {
  return /(?:\.([^.]+))?$/.exec(input);
  // return input.split('.').pop();
};

/**
 * Trims the file extension from a String.
 * @source https://stackoverflow.com/a/4250408
 *
 * @param {String} input
 * @returns {String}
 */
const getName = (input) => {
  return input.replace(/\.[^/.]+$/, '');
};

export {
  getName,
  getExt
};
