
const shebaPattern = /IR[0-9]{24}/;
const wrongCodeList = {
  1111111111: true,
  2222222222: true,
  3333333333: true,
  4444444444: true,
  5555555555: true,
  6666666666: true,
  7777777777: true,
  8888888888: true,
  9999999999: true,
  0: true
};

/**
 * @source https://www.npmjs.com/package/iran-basic
 *
 * @param {String} input
 * @returns {Bool} valid
 */
const codemelli = (input) => {
  const code = parseInt(input);
  if (wrongCodeList[code] || code < 10000000 || code > 9999999999) return false;

  const c = code % 10;
  let s = 0;
  for (let i = 10; i > 1; i--) {
    s += Math.floor(code / Math.pow(10, i - 1)) % 10 * i;
  }
  s = s % 11;
  return (s < 2) ? s === c : (11 - s) === c;
};

/**
 * @description Verify Iranian Bank's card number which is valid or not
 * @source https://github.com/persian-tools/persian-tools/blob/master/src/modules/verifyCardNumber/index.ts
 *
 * @category Bank account
 * @public
 * @method verifyCardNumber
 * @param {number} digits - card number
 * @return {boolean}
 */
const verifyCardNumber = (digits) => {
  if (!digits) return;
  const digitsResult = String(digits);

  const length = digitsResult.length;

  if (
    length < 16 ||
    parseInt(digitsResult.substr(1, 10), 10) === 0 ||
    parseInt(digitsResult.substr(10, 6), 10) === 0
  ) {
    return false;
  }

  let radix;
  let subDigit;
  let sum = 0;

  for (let i = 0; i < 16; i++) {
    radix = i % 2 === 0 ? 2 : 1;

    subDigit = parseInt(digitsResult.substr(i, 1), 10) * radix;
    sum += subDigit > 9 ? subDigit - 9 : subDigit;
  }

  return sum % 10 === 0;
};

const shebaIso7064Mod97 = (iban) => {
  let remainder = iban;
  let block;

  while (remainder.length > 2) {
    block = remainder.slice(0, 9);
    remainder = (parseInt(block, 10) % 97) + remainder.slice(block.length);
  }

  return parseInt(remainder, 10) % 97;
};

// https://github.com/persian-tools/persian-tools/blob/master/src/modules/sheba/index.ts
const isShebaValid = (shebaCode) => {
  shebaCode = shebaCode.toUpperCase();

  if (!shebaCode.startsWith('IR')) shebaCode = `IR${shebaCode}`;

  if (shebaCode.length !== 26) {
    return false;
  }

  if (!shebaPattern.test(shebaCode)) {
    return false;
  }

  const d1 = shebaCode.charCodeAt(0) - 65 + 10;
  const d2 = shebaCode.charCodeAt(1) - 65 + 10;

  let newStr = shebaCode.substr(4);
  newStr += d1.toString() + d2.toString() + shebaCode.substr(2, 2);

  const remainder = shebaIso7064Mod97(newStr);

  return remainder === 1;
};

export {
  codemelli,
  isShebaValid,
  verifyCardNumber
};
