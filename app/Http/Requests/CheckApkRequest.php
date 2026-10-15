<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckApkRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Isi file tetap dicek sebagai ZIP oleh ApkAnalyzer; ekstensi hanya penyaring awal.
            'apk' => ['required', 'file', 'extensions:apk', 'max:'.intdiv(config('sayno.apk.max_bytes'), 1024)],
        ];
    }
}
