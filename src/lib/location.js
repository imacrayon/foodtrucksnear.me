export const distance = (lat1, lon1, lat2, lon2, unit = 'M') => {
  var radlat1 = Math.PI * lat1 / 180
  var radlat2 = Math.PI * lat2 / 180
  var theta = lon1 - lon2
  var radtheta = Math.PI * theta / 180
  var dist = Math.sin(radlat1) * Math.sin(radlat2) + Math.cos(radlat1) * Math.cos(radlat2) * Math.cos(radtheta)
  dist = Math.acos(dist)
  dist = dist * 180 / Math.PI
  dist = dist * 60 * 1.1515
  if (unit === 'K') { dist = dist * 1.609344 } else if (unit === 'N') { dist = dist * 0.8684 }
  return dist
}

export const currentPosition = () => {
  return new Promise((resolve, reject) => {
    if (window.navigator.geolocation) {
      window.navigator.geolocation.getCurrentPosition(({ coords }) => {
        resolve(coords)
      })
    } else {
      // TODO: Make a better error message and offer a resolution.
      reject('Sorry, geolocation is not supported by this browser.')
    }
  })
}
