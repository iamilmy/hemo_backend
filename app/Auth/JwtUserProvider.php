<?php

namespace App\Auth;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash; // Use Hash facade for password checks
use App\Models\User; // Make sure your User model is correctly imported

class JwtUserProvider implements UserProvider
{
    protected $model;

    public function __construct($hasher, $model)
    {
        $this->model = $model;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        return (new $this->model)->find($identifier);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        // This method is generally not used for JWT.
        // JWTs are stateless, so no "remember me" token is typically stored on the server.
        return null;
    }

    /**
     * Update the "remember me" token for the given user in the storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        // This method is generally not used for JWT.
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        // This method is used by Auth::attempt() and similar.
        // For JWT, login is typically handled by manually creating a token after successful credential validation.
        if (empty($credentials) ||
            (count($credentials) === 1 &&
             array_key_exists('password', $credentials))) {
            return null;
        }

        // The 'user' model is used here based on the 'model' config in auth.php
        $query = (new $this->model)->newQuery();

        foreach ($credentials as $key => $value) {
            if (! str_contains($key, 'password')) {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // This method is used by Auth::attempt() and similar.
        // For JWT, password validation is usually done manually (Hash::check) before token creation.
        return Hash::check($credentials['password'], $user->getAuthPassword());
    }
}