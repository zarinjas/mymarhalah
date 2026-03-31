<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login_type' => ['nullable', 'in:admin,member'],
            'email' => ['required_if:login_type,admin', 'nullable', 'string', 'email'],
            'ic_number' => ['required_if:login_type,member', 'nullable', 'string', 'max:32'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginType = $this->string('login_type')->toString();
        $identifierField = $loginType === 'member' ? 'ic_number' : 'email';

        $credentials = [
            'password' => $this->input('password'),
        ];

        if ($identifierField === 'email') {
            $credentials['email'] = Str::lower(trim((string) $this->input('email')));
        } else {
            $credentials['ic_number'] = $this->normalizedIcNumber();
        }

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                $identifierField => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            $this->identifierField() => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        $identifier = $this->identifierField() === 'ic_number'
            ? $this->normalizedIcNumber()
            : Str::lower(trim((string) $this->input('email')));

        return Str::transliterate($identifier.'|'.$this->ip());
    }

    private function identifierField(): string
    {
        if ($this->string('login_type')->toString() === 'member') {
            return 'ic_number';
        }

        if ($this->filled('ic_number') && ! $this->filled('email')) {
            return 'ic_number';
        }

        return 'email';
    }

    private function normalizedIcNumber(): string
    {
        $raw = (string) $this->input('ic_number');

        return Str::upper(preg_replace('/\s+/', '', trim($raw)) ?? '');
    }
}
