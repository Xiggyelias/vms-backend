<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'identifier' => 'required|string|max:255',
            'password' => 'required|string|min:12',
            'userType' => 'required|in:student,staff',
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
            'identifier.required' => 'Please enter your email or registration number.',
            'password.required' => 'Please enter your password.',
            'password.min' => 'Password must be at least 12 characters.',
            'userType.required' => 'Please select your user type.',
            'userType.in' => 'Invalid user type selected.',
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
            'identifier' => 'email or registration number',
            'userType'   => 'user type',
        ];
    }
}







