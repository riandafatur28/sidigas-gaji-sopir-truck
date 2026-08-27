<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRitaseRequest extends FormRequest
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
            'kode_sopir' => 'required|exists:sopirs,kode_sopir',
            'kode_tujuan' => 'required|exists:tujuans,kode_tujuan',
            'tanggal' => 'required|date',
            'waktu' => 'required|in:pagi,malam',
            'kabupaten' => 'required|in:Nganjuk,Kediri,Kota Kediri,Jombang,Lainnya',
            'status' => 'required|in:valid,pending,gagal_produksi',
            'nominal_kompensasi' => 'nullable',
            'catatan' => 'nullable|string|max:500',
            'is_lembur' => 'nullable|in:0,1',
            'upah_lembur' => 'nullable|numeric|min:0',
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
            'kode_sopir.required' => 'Sopir wajib dipilih.',
            'kode_sopir.exists' => 'Sopir yang dipilih tidak valid.',
            'kode_tujuan.required' => 'Tujuan wajib dipilih.',
            'kode_tujuan.exists' => 'Tujuan yang dipilih tidak valid.',
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Format tanggal tidak valid.',
            'waktu.required' => 'Waktu wajib dipilih.',
            'waktu.in' => 'Waktu harus pagi atau malam.',
            'kabupaten.required' => 'Kabupaten wajib dipilih.',
            'kabupaten.in' => 'Kabupaten tidak valid.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
            'catatan.max' => 'Catatan maksimal 500 karakter.',
            'is_lembur.in' => 'Format lembur tidak valid.',
            'upah_lembur.numeric' => 'Upah lembur harus berupa angka.',
            'upah_lembur.min' => 'Upah lembur tidak boleh negatif.',
        ];
    }
}
