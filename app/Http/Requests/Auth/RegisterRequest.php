<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'username' => ['required','string','min:4','max:32','unique:users,username'],
            'name' => ['required','string','max:100'],
            'optional_name' => ['nullable','string','max:100'],
            'email' => ['required','email','unique:users,email'],
            'country_iso' => ['nullable','string','max:3'],
            'password' => ['required','string','min:8'],
        ];
    }
}
