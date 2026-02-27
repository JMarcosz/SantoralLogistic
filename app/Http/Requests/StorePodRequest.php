<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'happened_at' => ['required', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'image' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'happened_at' => 'fecha/hora',
            'latitude' => 'latitud',
            'longitude' => 'longitud',
            'image' => 'imagen',
            'notes' => 'notas',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'happened_at.required' => 'La fecha/hora es requerida.',
            'happened_at.date' => 'La fecha/hora debe ser válida.',
            'latitude.between' => 'La latitud debe estar entre -90 y 90.',
            'longitude.between' => 'La longitud debe estar entre -180 y 180.',
            'image.max' => 'La imagen no debe superar los 10MB.',
            'image.mimes' => 'La imagen debe ser de tipo: jpg, jpeg, png, gif o webp.',
            'notes.max' => 'Las notas no deben superar los 1000 caracteres.',
        ];
    }
}
