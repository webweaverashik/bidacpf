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
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'password'      => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'role'          => ['required', 'string', 'in:' . implode(',', $validRoles)],
        ];
    }

    public function attributes(): array
    {
        return [
            'mobile_number' => 'mobile number',
        ];
    }
}
