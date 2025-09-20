
const convertDMSToDD = (degrees, minutes, seconds, direction) => {
  // let dd = degrees + minutes / 60 + seconds / (60 * 60);
  let dd = Number(degrees) + Number(minutes) / 60 + Number(seconds) / (60 * 60);

  // Don't do anything for N or E
  if (direction === 'S' || direction === 'W') {
    dd = dd * -1;
  }

  return dd;
};

// 36°57'9" N  = 36.9525000
// 110°4'21" W = -110.0725000
// @source https://stackoverflow.com/a/1140335
const parseDMS = (input) => {
  const parts = input.split(/[^\d\w]+/);

  return [
    convertDMSToDD(parts[0], parts[1], parts[2], parts[3]), // lat
    convertDMSToDD(parts[4], parts[5], parts[6], parts[7]) // long
  ];
};

// `Haversine` formula to find distance between two points on a sphere
// @source https://www.geeksforgeeks.org/dsa/haversine-formula-to-find-distance-between-two-points-on-a-sphere/
const haversine = (lat1, lon1, lat2, lon2) => {
  // distance between latitudes and longitudes
  const dLat = (lat2 - lat1) * Math.PI / 180.0;
  const dLon = (lon2 - lon1) * Math.PI / 180.0;

  // convert to radiansa
  lat1 = (lat1) * Math.PI / 180.0;
  lat2 = (lat2) * Math.PI / 180.0;

  // apply formula
  const a = Math.pow(Math.sin(dLat / 2), 2) + Math.pow(Math.sin(dLon / 2), 2) * Math.cos(lat1) * Math.cos(lat2);

  const rad = 6371;
  const c = 2 * Math.asin(Math.sqrt(a));

  return rad * c;
};

export {
  parseDMS,
  convertDMSToDD,
  haversine
};
