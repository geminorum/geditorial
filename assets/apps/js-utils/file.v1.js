
/**
 * Convert size in bytes to human readable format.
 * @source https://gist.github.com/zentala/1e6f72438796d74531803cc3833c039c
 *
 * @param {Int} bytes
 * @param {Int} decimals
 * @returns {String}
 */
const formatBytes = (bytes, decimals) => {
  if (bytes === 0) return '0 Bytes';
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  return parseFloat((bytes / Math.pow(1024, i)).toFixed(decimals || 2)) + ' ' + sizes[i];
};

// @source https://stackoverflow.com/a/15270931
const baseName = (input) => input.split(/[\\/]/).pop();

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
const getExt = (input) => /(?:\.([^.]+))?$/.exec(input);
// const getExt = (input) => input.split('.').pop();

/**
 * Trims the file extension from a String.
 * @source https://stackoverflow.com/a/4250408
 *
 * @param {String} input
 * @returns {String}
 */
const getName = (input) => input.replace(/\.[^/.]+$/, '');

export {
  formatBytes,
  baseName,
  getName,
  getExt
};
