import {
  // isNumericString,
  // toPersian,
  toEnglish
} from './number.v1';

// https://github.com/shams-nahid/validate-phone-number-node-js
const validatePhoneNumber = (phoneNumber) => {
  const pattern = /^\+{0,2}([-. ])?(\(?\d{0,3}\))?([-. ])?\(?\d{0,3}\)?([-. ])?\d{3}([-. ])?\d{4}/;
  return pattern.test(toEnglish(phoneNumber));
};

/**
 * Checks if the given phone number is a valid Persian phone number.
 *
 * @source https://github.com/mohammadrezahayati/persian_util/blob/main/src/validation/phoneNumber/checkPersianPhone.ts
 *
 * @param {string} phoneNumber - The phone number to check.
 * @returns {boolean} - True if the phone number is valid, false otherwise.
 */
const checkPersianMobile = (phoneNumber) => {
  const pattern = /^(\+98|0098|98|0)?9\d{9}$/;
  return pattern.test(toEnglish(phoneNumber));
};

/**
 * take number and check it if from MCI Hamrah Aval Operator or not
 *
 * @source https://github.com/mohammadrezahayati/persian_util/blob/main/src/validation/phoneNumber/isMci.ts
 *
 * @param phoneNumber
 * @returns {boolean}
 */
const isMCI = (phoneNumber) => {
  const pattern = /^(\+98|0098|98|0)?(990|991|992|993|994|995|996|910|911|912|913|914|915|916|917|918|919)\d{7}$/;
  return pattern.test(toEnglish(phoneNumber));
};

/**
 * take number and check it if from MTN Irancell Operator or not
 *
 * @source https://github.com/mohammadrezahayati/persian_util/blob/main/src/validation/phoneNumber/isMtn.ts
 *
 * @param phoneNumber
 * @returns {boolean}
 */
const isMTN = (phoneNumber) => {
  const pattern = /^(\+98|0098|98|0)?(930|933|935|936|937|938|900|901|902|903|904|905|941)\d{7}$/;
  return pattern.test(toEnglish(phoneNumber));
};

/**
 * take number and check it if from Rightel Operator or not
 *
 * @source https://github.com/mohammadrezahayati/persian_util/blob/main/src/validation/phoneNumber/isRightel.ts
 *
 * @param phoneNumber
 * @returns {boolean}
 */
const isRightel = (phoneNumber) => {
  const pattern = /^(\+98|0098|98|0)?(920|921|922|923)\d{7}$/;
  return pattern.test(toEnglish(phoneNumber));
};

const checkMobileByLocale = (phoneNumber, locale) => {
  if (!validatePhoneNumber(phoneNumber)) return false;
  return locale === 'fa-IR' ? checkPersianMobile(phoneNumber) : true;
};

/**
 * @source https://www.npmjs.com/package/iran-basic
 *
 * @param {String} phoneNumber
 * @returns {boolean}
 */
const mobile = (phoneNumber) => {
  const mobileNo = parseInt(phoneNumber);
  let ret = (mobileNo > 9009999999 && mobileNo < 10000000000);
  ret |= (mobileNo > 989009999999 && mobileNo < 9810000000000);
  return ret;
};

/**
 * @source https://www.npmjs.com/package/iran-basic
 *
 * @param {String} phoneNumber
 * @returns {String}
 */
const fixMobile = (phoneNumber) => {
  let mobileNo = parseInt(phoneNumber);
  if (mobileNo > 9009999999 && mobileNo < 10000000000) {
    mobileNo += 980000000000;
  }
  return mobileNo;
};

export {
  mobile, // FIXME: rename this!
  fixMobile, // FIXME: rename this!

  checkPersianMobile,
  isMCI,
  isMTN,
  isRightel,
  validatePhoneNumber,
  checkMobileByLocale
};
