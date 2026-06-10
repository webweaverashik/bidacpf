<?php
namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.create');
    }

    public function rules(): array
    {
        $validRoles = Role::pluck('name')->toArray();

        return [
            'name'          => ['required', 'string', 'max:255'],
            'designation'   => ['nullable', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile_number' => ['nullable', 'string', 'size:11', 'regex:/^01[3-9]\d{8}$/'],
            'password'      => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'role'          => ['required', 'string', 'in:' . implode(',', $validRoles)],
            'photo_url'     => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'mobile_number' => 'mobile number',
        ];
    }

    public function messages(): array
    {
        return [
            'mobile_number.regex' => 'Please enter a valid 11-digit Bangladeshi mobile number.',
            'photo_url.max'       => 'Image size must be less than 100KB.',
        ];
    }
}
