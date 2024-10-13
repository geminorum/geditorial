import {
  // isNumericString,
  // padWith,
  // inRange,
  toEnglish
} from './number.v1';

const vinPattern = /[a-zA-Z0-9]{9}[a-zA-Z0-9-]{2}[0-9]{6}/; // https://www.regextester.com/100058
// const vinPatternIndian = /^[A-Z]{2}[ -][0-9]{1,2}(?: [A-Z])?(?: [A-Z]*)? [0-9]{4}$/; // https://www.geeksforgeeks.org/validating-indian-vehicle-number-plate-using-regualr-expression/
// const vinPatternChina = /^[A-HJ-NPR-Z0-9]{17}$/; // https://www.php.cn/faq/565691.html

const verifyVinNumber = (input) => {
  if (!input) return false;
  input = toEnglish(input.toUpperCase());
  if (input.length !== 17) return false;
  if (!vinPattern.test(input)) return false;
  return true;
};

/**
 * Checks for proper length, characters, and a valid check digit.
 * @source https://github.com/wegolook/vin-validator
 *
 * @param {String} vin
 * @returns {Bool} valid
 */
const validateVin = (vin) => {
  if (!vin) {
    return false;
  }

  vin = toEnglish(vin.toLowerCase());

  if (!/^[a-hj-npr-z0-9]{8}[0-9xX][a-hj-npr-z0-9]{8}$/.test(vin)) {
    return false;
  }

  const transliterationTable = {
    0: 0,
    1: 1,
    2: 2,
    3: 3,
    4: 4,
    5: 5,
    6: 6,
    7: 7,
    8: 8,
    9: 9,
    a: 1,
    b: 2,
    c: 3,
    d: 4,
    e: 5,
    f: 6,
    g: 7,
    h: 8,
    j: 1,
    k: 2,
    l: 3,
    m: 4,
    n: 5,
    p: 7,
    r: 9,
    s: 2,
    t: 3,
    u: 4,
    v: 5,
    w: 6,
    x: 7,
    y: 8,
    z: 9
  };

  const weightsTable = [8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2];
  let sum = 0;

  for (let i = 0; i < vin.length; ++i) {
    sum += transliterationTable[vin.charAt(i)] * weightsTable[i];
  }

  const mod = sum % 11;
  return mod === 10 ? vin.charAt(8) === 'x' : vin.charAt(8) === mod;
};

/**
 * THIS FUNCTION IS USED TO DETERMINE IF THE VIN NUMBER IS VALID
 * BY CALCULATING A CORRECT CHECK DIGIT USED IN EVERY VIN (9th DIGIT FROM LEFT)
 * THIS IS ONLY VALID IN VEHICLES MADE SINCE 1980
 * @author rfink
 * @since  ~01/01/2007
 * @source https://github.com/rfink/angular-vin/blob/master/vinvalidator.js
 */
const validateVinALT = (vin, year) => {
  vin = toEnglish(vin);

  if (year) {
    year = toEnglish(year);

    // IF THE YEAR IS IN THE CORRECT RANGE, DETERMINE THE LENGTH.
    // LENGTH MUST BE 17 DIGITS, IF LESS, alert
    if (parseInt(year) >= 1980) {
      if (vin.length < 17) {
      // alert('The vin you entered is not long enough.');
        return false;
      }
    } else {
      return true;
    }
  }

  // START BUILDING THE ARRAY FOR THE CALCULATIONS
  const vinChars = new Array(23);

  for (let i = 0; i < 23; ++i) {
    vinChars[i] = new Array(2);
  }

  // THESE ARE THE CORRESPONDING VALUES GIVEN TO ALPHABETIC DIGITS IN THE VIN
  vinChars[0][0] = 'A';
  vinChars[0][1] = 1;
  vinChars[1][0] = 'B';
  vinChars[1][1] = 2;
  vinChars[2][0] = 'C';
  vinChars[2][1] = 3;
  vinChars[3][0] = 'D';
  vinChars[3][1] = 4;
  vinChars[4][0] = 'E';
  vinChars[4][1] = 5;
  vinChars[5][0] = 'F';
  vinChars[5][1] = 6;
  vinChars[6][0] = 'G';
  vinChars[6][1] = 7;
  vinChars[7][0] = 'H';
  vinChars[7][1] = 8;
  vinChars[8][0] = 'J';
  vinChars[8][1] = 1;
  vinChars[9][0] = 'K';
  vinChars[9][1] = 2;
  vinChars[10][0] = 'L';
  vinChars[10][1] = 3;
  vinChars[11][0] = 'M';
  vinChars[11][1] = 4;
  vinChars[12][0] = 'N';
  vinChars[12][1] = 5;
  vinChars[13][0] = 'P';
  vinChars[13][1] = 7;
  vinChars[14][0] = 'R';
  vinChars[14][1] = 9;
  vinChars[15][0] = 'S';
  vinChars[15][1] = 2;
  vinChars[16][0] = 'T';
  vinChars[16][1] = 3;
  vinChars[17][0] = 'U';
  vinChars[17][1] = 4;
  vinChars[18][0] = 'V';
  vinChars[18][1] = 5;
  vinChars[19][0] = 'W';
  vinChars[19][1] = 6;
  vinChars[20][0] = 'X';
  vinChars[20][1] = 7;
  vinChars[21][0] = 'Y';
  vinChars[21][1] = 8;
  vinChars[22][0] = 'Z';
  vinChars[22][1] = 9;

  // HERE IS THE ARRAY FOR THE WEIGHTS GIVEN TO THE SPECIFIC DIGITS OF THE VIN
  const vinWeights = new Array(17);

  // HERE ARE THE VALUES ASSOCIATED WITH THE WEIGHTS
  vinWeights[0] = 8;
  vinWeights[1] = 7;
  vinWeights[2] = 6;
  vinWeights[3] = 5;
  vinWeights[4] = 4;
  vinWeights[5] = 3;
  vinWeights[6] = 2;
  vinWeights[7] = 10;
  // (THE CHECK DIGIT IS NOT GIVEN A WEIGHT)
  vinWeights[9] = 9;
  vinWeights[10] = 8;
  vinWeights[11] = 7;
  vinWeights[12] = 6;
  vinWeights[13] = 5;
  vinWeights[14] = 4;
  vinWeights[15] = 3;
  vinWeights[16] = 2;

  // NOW WE INSERT EACH DIGIT INTO AN ARRAY
  const vinNums = new Array(17);

  for (let i = 0; i < 17; ++i) {
    vinNums[i] = vin.substring(i, i + 1);
  }

  // INITIALIZE SUM VARIABLE
  let sum = 0;

  // HERE, WE CYCLE THROUGH THE DIGIT ARRAY, MULTIPLYING THE ASSOCIATED VALUE
  // WITH ITS WEIGHT, AND ADDING IT TO THE SUM
  for (let i = 0; i < 17; ++i) {
    if (i === 8) {
      continue;
    }

    if (isNaN(vinNums[i])) {
      for (let j = 0; j < 23; ++j) {
        if (vinChars[j][0] === vinNums[i]) {
          sum += (vinChars[j][1] * vinWeights[i]);
          break;
        }
      }
    } else {
      sum += (vinNums[i] * vinWeights[i]);
    }
  }

  // NOW TAKE THE REMAINDER OF THE SUM DIVIDED BY 11
  // IF IT EQUALS 10, THEN THE CHECK DIGIT SHOULD BE 'X'
  let checkDigit = sum % 11;
  if (checkDigit === 10) {
    checkDigit = 'X';
  }

  // NOW COMPARE IT WITH THE ACTUAL CHECK DIGIT GIVEN
  // IF IT IS INCORRECT, ALERT

  if (checkDigit !== vinNums[8]) {
    // alert('You have entered an incorrect vin #.  Please check and correct as necessary.  Remember that the letters 'o','q' and 'i' are not used.');
    return false;
  }

  return true;
};

export {
  validateVin,
  verifyVinNumber,
  validateVinALT
};
