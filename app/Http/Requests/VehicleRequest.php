<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // System uses custom session auth, not Laravel's Auth guard
        return (bool) session('logged_in') && (bool) session('user_id');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'make' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'PlateNumber' => 'nullable|string|max:20',
            'owner' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
        ];

        if ($this->isMethod('post')) {
            // Creating new vehicle
            $rules['regNumber'] = 'required|string|max:50|unique:vehicles,regNumber';
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            // Updating existing vehicle
            $vehicleId = $this->route('vehicle') ?? $this->route('id');
            $rules['regNumber'] = [
                'required',
                'string',
                'max:50',
                Rule::unique('vehicles', 'regNumber')->ignore($vehicleId, 'vehicle_id')
            ];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'make.required' => 'Please enter the vehicle make.',
            'regNumber.required' => 'Please enter the registration number.',
            'regNumber.unique' => 'This registration number is already registered.',
            'make.max' => 'Vehicle make cannot exceed 255 characters.',
            'model.max' => 'Vehicle model cannot exceed 255 characters.',
            'PlateNumber.max' => 'Plate number cannot exceed 20 characters.',
            'owner.max' => 'Owner name cannot exceed 255 characters.',
            'address.max' => 'Address cannot exceed 500 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'make' => 'vehicle make',
            'model' => 'vehicle model',
            'regNumber' => 'registration number',
            'PlateNumber' => 'plate number',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Sanitize input data
        $this->merge([
            'make' => trim($this->make),
            'model' => $this->model ? trim($this->model) : null,
            'regNumber' => trim($this->regNumber),
            'PlateNumber' => $this->PlateNumber ? trim($this->PlateNumber) : null,
            'owner'   => $this->owner   ? trim($this->owner)   : null,
            'address' => $this->address ? trim($this->address) : null,
        ]);
    }
}







