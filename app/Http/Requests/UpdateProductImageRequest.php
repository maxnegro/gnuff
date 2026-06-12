<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image_base64' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // Validare che sia un Base64 valido con prefisso data URI
                    if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,.+$/i', $value)) {
                        $fail('The '.$attribute.' must be a valid Base64 encoded image (JPEG, PNG, WebP) with data URI format.');
                    }

                    // Estrarre e decodificare il Base64
                    $base64Data = preg_replace('/^data:image\/(jpeg|jpg|png|webp);base64,/i', '', $value);
                    $decodedImage = base64_decode($base64Data, true);

                    if ($decodedImage === false) {
                        $fail('The '.$attribute.' is not valid Base64 encoded data.');

                        return;
                    }

                    // Controllare la dimensione (max 5MB)
                    $maxBytes = 5 * 1024 * 1024; // 5MB
                    if (strlen($decodedImage) > $maxBytes) {
                        $fail('The '.$attribute.' must not exceed 5 MB.');
                    }

                    // Validare che sia effettivamente un'immagine valida
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    if ($finfo === false) {
                        $fail('Unable to validate image type.');

                        return;
                    }

                    $mimeType = finfo_buffer($finfo, $decodedImage);
                    finfo_close($finfo);

                    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                    if (! in_array($mimeType, $allowedMimes, true)) {
                        $fail('The '.$attribute.' must be a JPEG, PNG, or WebP image. Detected: '.$mimeType);
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image_base64.required' => 'An image is required.',
        ];
    }
}
