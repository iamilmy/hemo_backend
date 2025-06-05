<?php

namespace App\Auth;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User; // Make sure your User model is imported

class JwtGuard implements Guard
{
    use GuardHelpers;

    protected $provider;
    protected $request;
    protected $secret;
    protected $algo;
    protected $user; // To store the authenticated user

    public function __construct(
        $hasher, // Aunque tidak langsung digunakan oleh Guard, bisa jadi dari dependency AuthManager
        UserProvider $provider,
        Request $request,
        $secret,
        $algo = 'HS256'
    ) {
        $this->provider = $provider;
        $this->request = $request;
        $this->secret = $secret;
        $this->algo = $algo;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        // Get the token from the request header
        $token = $this->request->bearerToken(); // This gets the token from 'Bearer TOKEN'

        if (! $token) {
            return $this->user = null; // No token provided
        }

        try {
            // Decode the token
            $credentials = JWT::decode($token, new Key($this->secret, $this->algo));

            // Retrieve the user using the provider's retrieveById method
            return $this->user = $this->provider->retrieveById($credentials->sub);

        } catch (\Firebase\JWT\ExpiredException $e) {
            // Token is expired
            return $this->user = null;
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            // Token signature is invalid
            return $this->user = null;
        } catch (\Exception $e) {
            // Other decoding errors
            return $this->user = null;
        }
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        // This method is generally used for session-based authentication (Auth::attempt)
        // For JWT, the token itself is the validation.
        // However, if you need to use this for initial login (e.g. if Auth::attempt is called)
        // you might implement it like this:
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user && $this->provider->validateCredentials($user, $credentials)) {
            $this->setUser($user);
            return true;
        }

        return false;
    }

    // The remaining methods of Guard interface:
    public function check()
    {
        return ! is_null($this->user());
    }

    public function guest()
    {
        return ! $this->check();
    }

    public function id()
    {
        if ($user = $this->user()) {
            return $user->getAuthIdentifier();
        }
    }

    public function hasUser()
    {
        return ! is_null($this->user());
    }

    public function setUser(\Illuminate\Contracts\Auth\Authenticatable $user)
    {
        $this->user = $user;
    }
}