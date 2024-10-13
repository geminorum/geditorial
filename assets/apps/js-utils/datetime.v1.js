import {
  isNumericString,
  padWith,
  inRange,
  toEnglish
} from './number.v1';

/**
 * Replaces the year with the day on given date string.
 * @test https://regex101.com/r/ZacQY5/1
 *
 * @param {String} input
 * @returns {String}
 */
const flipDateString = (input) => {
  return toEnglish(input)
    .replace(/([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})/gmiu, '$3/$2/$1');
};

const verifyYearString = (input) => {
  input = toEnglish(input);
  if (!isNumericString(input)) return false;
  if (!(/^\d{4}$/.exec(input))) return false;
  if (!inRange(+input, 1001, 2999)) return false;
  return true;
};

const verifyDateString = (input, sep) => {
  const parsed = /([0-9]{1,4})\/([0-9]{1,2})\/([0-9]{1,4})/gmiu.exec(toEnglish(input));

  if (!parsed) return false;
  if (+parsed[1] === 0) return false;
  if (!inRange(+parsed[2], 1, 12)) return false;
  if (!inRange(+parsed[3], 1, 31)) return false;

  return true;
};

const sanitizeDateString = (input, sep) => {
  return toEnglish(input)
    .replace(/([0-9]{1,4})\/([0-9]{1,2})\/([0-9]{1,4})/gmiu, (matched, y, m, d) => {
      let temp;

      // flipped
      if (d.length === 4 || d > 31) {
        temp = y;
        y = d;
        d = temp;
      }

      return [y, padWith(m, 2), padWith(d), 2].join(sep || '/');
    });
};

/**
 * @source https://stackoverflow.com/a/75175014
 *
 * Accepts "1998-08-06 11:00:00" <-- This is UTC timestamp
 * Returns "August 6, 1998 | 11:00 AM" <-- This is converted to client time zone.
 *
 * @param {String} input
 * @returns {String} formatted
 */
const getFormalDateTime = (input) => {
  const formattedUtc = input.split(' ').join('T') + 'Z';
  const date = new Date(formattedUtc);
  if (date.toString() === 'Invalid Date') return 'N/A';

  const dateString = date.toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric'
  });

  const timeString = date.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: 'numeric',
    hour12: true
  });

  return dateString + ' | ' + timeString;
};

/**
 * @source https://stackoverflow.com/a/75175014
 *
 * Accepts: "1998-08-06"
 * Returns "August 6, 1998"
 *
 * @param {String} input
 * @returns {String} formatted
 */
const getFormalDate = (input) => {
  const date = new Date(input);
  if (date.toString() === 'Invalid Date') return 'N/A';
  return date.toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric'
  });
};

/**
 * To direct get a readable local timezone
 * @source https://stackoverflow.com/a/71228061
 *
 * @param {String} input
 * @returns {String} formatted
 */
const readableTimestamp = (input) => {
  const date = new Date(input * 1000);
  return date.toLocaleString();
};

/**
 * Convert timestamp to decimal format
 * @source https://github.com/harvesthq/hour-parser
 *
 * toDecimal('0.5') // 0.50
 * toDecimal('0:5') // 0.08
 * toDecimal('1.25') // 1.25
 * toDecimal('1,25') // 1.25
 * toDecimal('1.5+3') // 4.50
 * toDecimal('1:45+3') // 4.75
 *
 * @param {number|string} input? Timestamp to convert
 * @returns {string} A timestamp in decimal format (rounded/padded to 2 decimals places)
 */
const toDecimal = (input) => {
  if (!input && input !== 0) {
    return '';
  }
  if (typeof input === 'number') {
    return input.toFixed(2);
  }
  const hours = evalInput(input) / 60;
  return isNaN(hours) ? '' : hours.toFixed(2).toString();
};

/**
 * Convert timestamp to hh:mm format
 * @source https://github.com/harvesthq/hour-parser
 *
 * toHHMM('0.5') // 0:30
 * toHHMM('0:5') // 0:05
 * toHHMM('1.25') // 1:15
 * toHHMM('1,25') // 1:15
 * toHHMM('1.5+3') // 4:30
 * toHHMM('1:45+3') // 4:45
 *
 * @param {number|string} input? Timestamp to convert
 * @returns {string} A timestamp in hh:mm format
 */
const toHHMM = (input) => {
  if (!input && input !== 0) {
    return '';
  }
  let total = evalInput(input);
  if (isNaN(total)) {
    return '';
  }
  const sign = total < 0 ? '-' : '';
  total = Math.abs(total);
  const hours = Math.floor(total / 60);
  let minutes = total % 60;
  if (minutes >= 59.5 && minutes < 60) {
    minutes = Math.floor(minutes);
  } else {
    minutes = Math.round(minutes);
  }
  const paddedMinutes = minutes.toString().padStart(2, '0');
  return `${sign}${hours}:${paddedMinutes}`;
};

// Helpers
// @source https://github.com/harvesthq/hour-parser
const parseNumber = (input) => {
  const [hours, minutes] = input
    .toString()
    .replace(/,/g, '.')
    .replace(/\s/g, '')
    .split(':');
  const sign = /^\s*-/.test(hours) ? '-' : '';
  return 60 * parseFloat(hours || '0') + parseFloat(sign + (minutes || '0'));
};

// @source https://github.com/harvesthq/hour-parser
const evalInput = (input) => {
  const adder = (sum, match) => sum + parseNumber(match);
  return input
    .toString()
    .match(/\s*[+-]?[^+-]+/g)
    .reduce(adder, 0);
};

export {
  toDecimal,
  toHHMM,

  flipDateString,
  verifyYearString,
  verifyDateString,
  sanitizeDateString,

  getFormalDateTime,
  getFormalDate,

  readableTimestamp
};
