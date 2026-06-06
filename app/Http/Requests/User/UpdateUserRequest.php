<?php
namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.update');
    }

    public function rules(): array
    {
        $userId     = $this->route('user')->id;
        $validRoles = Role::pluck('name')->toArray();

        return [
            'name'        => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255', "unique:users,email,{$userId}"],
            'mobile_number' => ['nullable', 'string', 'max:20'], // Password is optional on update — only validated if filled
            'password'      => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
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
