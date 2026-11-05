<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id|integer|in:1',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id|integer',
            'items.*.quantity' => 'required|integer|min:1'
    ];
    }
    public function messages(): array
    {
        return [
            'user_id.required' => 'El Usuario es obligatorio.',
            'user_id.exists' => 'El Usuario proporcionado no existe en la tabla usuarios.',
            'user_id.integer' => 'El ID del usuario debe ser un número entero.',
            'user_id.in' => 'El ID del usuario debe ser 1',
            'items.required' => 'Los items son obligatorios.',
            'items.array' => 'El campo items debe ser un arreglo.',
            'items.min' => 'Debe haber al menos un item en la venta.',
            'items.*.product_id.required' => 'El Id del producto es obligatorio para cada item.',
            'items.*.product_id.exists' => 'El producto proporcionado no existe en la tabla products.',
            'items.*.product_id.integer' => 'El Id del producto debe ser un número entero.',
            'items.*.quantity.required' => 'La cantidad es obligatorio para cada item.',
            'items.*.quantity.integer' => 'La cantidad de cada item debe ser un número entero.',
            'items.*.quantity.min' => 'La cantidad de cada item debe ser al menos 1.'
        ];
    }
}
