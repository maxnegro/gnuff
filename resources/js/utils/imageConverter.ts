/**
 * Convert a File object to a Base64 Data URI string
 */
export function fileToDataUri(file: File): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()

    reader.onload = (e) => {
      if (typeof e.target?.result === 'string') {
        resolve(e.target.result)
      } else {
        reject(new Error('Impossibile convertire il file in Data URI'))
      }
    }

    reader.onerror = () => {
      reject(new Error('Errore nella lettura del file'))
    }

    reader.readAsDataURL(file)
  })
}
