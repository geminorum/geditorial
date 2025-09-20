/* eslint no-unused-vars: off */

import { codemelli, verifyCardNumber, isShebaValid } from './iranian.v1';
import { checkMobileByLocale } from './mobile.v1';
import { verifyDateString, verifyYearString } from './datetime.v1';
import { validateVin, verifyVinNumber } from './vehicle.v1';

const currentLocale = () => {
  return document.documentElement.getAttribute('lang') || 'en';
};

const isValidField = (value, field, locale) => {
  if (!value) return false;
  const current = locale || currentLocale();

  switch (field) {
    case 'identity':
      if (current === 'fa-IR') return codemelli(value);
      break;

    case 'mobile':
    case 'phone':
      return checkMobileByLocale(value, locale);

    case 'vin':
      return verifyVinNumber(value);
      // return validateVin(value);

    // case 'plate':

    case 'year':
      return verifyYearString(value);

    case 'date':
    case 'datetime':
    case 'datestart':
    case 'dateend':
    case 'date_of_death':
    case 'date_of_birth':
    case 'dob':
      if (current === 'fa-IR') return verifyDateString(value) || verifyYearString(value);
      break;

    case 'iban':
      if (current === 'fa-IR') return isShebaValid(value);
      break;

    case 'card':
      if (current === 'fa-IR') return verifyCardNumber(value);
      break;
  }

  return null;
};

export {
  currentLocale,
  isValidField
};
