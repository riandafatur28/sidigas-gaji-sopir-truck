<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePenggajianRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'periode_id' => 'required|exists:periodes,id',
            'detail' => 'required|array|min:1',
            'detail.*.kode_tujuan' => 'required|exists:tujuans,kode_tujuan',
            'detail.*.bbm_per_rit' => 'required|numeric|min:0',
            'detail.*.upah_per_rit' => 'required|numeric|min:0',
            'detail.*.tol_per_rit' => 'nullable|numeric|min:0',
            'detail.*.kompensasi_gagal' => 'nullable|numeric|min:0',
            'detail.*.lembur_per_rit' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'periode_id.required' => 'Periode wajib dipilih.',
            'periode_id.exists' => 'Periode yang dipilih tidak valid.',
            'detail.required' => 'Detail penggajian wajib diisi.',
            'detail.array' => 'Detail penggajian harus berupa array.',
            'detail.min' => 'Detail penggajian minimal 1 item.',
            'detail.*.kode_tujuan.required' => 'Kode tujuan wajib diisi.',
            'detail.*.kode_tujuan.exists' => 'Kode tujuan tidak valid.',
            'detail.*.bbm_per_rit.required' => 'BBM per rit wajib diisi.',
            'detail.*.bbm_per_rit.numeric' => 'BBM per rit harus berupa angka.',
            'detail.*.bbm_per_rit.min' => 'BBM per rit tidak boleh negatif.',
            'detail.*.upah_per_rit.required' => 'Upah per rit wajib diisi.',
            'detail.*.upah_per_rit.numeric' => 'Upah per rit harus berupa angka.',
            'detail.*.upah_per_rit.min' => 'Upah per rit tidak boleh negatif.',
            'detail.*.tol_per_rit.numeric' => 'Tol per rit harus berupa angka.',
            'detail.*.tol_per_rit.min' => 'Tol per rit tidak boleh negatif.',
            'detail.*.kompensasi_gagal.numeric' => 'Kompensasi gagal harus berupa angka.',
            'detail.*.kompensasi_gagal.min' => 'Kompensasi gagal tidak boleh negatif.',
            'detail.*.lembur_per_rit.numeric' => 'Lembur per rit harus berupa angka.',
            'detail.*.lembur_per_rit.min' => 'Lembur per rit tidak boleh negatif.',
        ];
    }
}
