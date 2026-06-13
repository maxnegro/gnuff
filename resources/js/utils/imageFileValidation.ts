/**
 * Validate image file size and type
 */
export function validateImageFile(
  file: File,
  maxSizeBytes = 5 * 1024 * 1024,
  allowedTypes = ['image/jpeg', 'image/png', 'image/webp']
): { isValid: boolean; error: string | null } {
  if (!allowedTypes.includes(file.type)) {
    return {
      isValid: false,
      error: 'Formato immagine non supportato. Usa JPEG, PNG o WEBP.',
    }
  }

  if (file.size > maxSizeBytes) {
    const maxSizeMB = maxSizeBytes / (1024 * 1024)
    return {
      isValid: false,
      error: `L'immagine supera la dimensione massima di ${maxSizeMB}MB.`,
    }
  }

  return { isValid: true, error: null }
}
