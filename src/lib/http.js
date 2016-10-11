export default function (url) {
  var core = {
    ajax (method, url, args) {
      const promise = new Promise((resolve, reject) => {
        const client = new window.XMLHttpRequest()
        let data = ''
        if (args) {
          let argcount = 0
          for (let key in args) {
            if (args.hasOwnProperty(key)) {
              if (argcount++) {
                data += '&'
              }
              data += encodeURIComponent(key) + '=' + encodeURIComponent(args[key])
            }
          }
          if (method !== 'POST' && method !== 'PUT') {
            url = url + '?' + data
          }
        }

        client.open(method, url)
        client.setRequestHeader('Content-type', 'application/x-www-form-urlencoded')
        client.send(data || null)

        client.onload = ({ target }) => {
          if (target.status >= 200 && target.status < 300) {
            resolve(JSON.parse(target.response))
          } else {
            reject(JSON.parse(target.response))
          }
        }
        client.onerror = ({ target }) => {
          reject(target.statusText)
        }
      })

      return promise
    }
  }

  return {
    get (args) {
      return core.ajax('GET', url, args)
    },
    post (args) {
      return core.ajax('POST', url, args)
    },
    put (args) {
      return core.ajax('PUT', url, args)
    },
    delete (args) {
      return core.ajax('DELETE', url, args)
    }
  }
}
