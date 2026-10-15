<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

/**
 * Hasil ekstraksi browser untuk APK besar: hanya AndroidManifest.xml, SHA-256, nama, dan ukuran.
 */
class CheckApkManifestRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'manifest' => ['required', 'file', 'max:'.intdiv(config('sayno.apk.manifest_max_bytes'), 1024)],
            'sha256' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'file_name' => ['required', 'string', 'max:255', 'regex:/\.apk$/i'],
            'file_size' => ['required', 'integer', 'min:1', 'max:'.config('sayno.apk.browser_max_bytes')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['sha256' => strtolower((string) $this->input('sha256'))]);
    }

    /**
     * Semua kesalahan ditampilkan sebagai satu pesan di area upload APK.
     */
    protected function failedValidation(Validator $validator): void
    {
        $message = $validator->errors()->has('file_size')
            ? __('web.home.apk_too_large')
            : __('result.apk_unreadable');

        throw new ValidationException(
            $validator,
            redirect()->to(url()->previous())->withErrors(['apk' => $message]),
        );
    }
}
