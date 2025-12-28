<?php

namespace App\Auth;

use App\Models\Staf;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;

class StafUserProvider implements UserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     */
    public function retrieveById($identifier)
    {
        return Staf::with('loginRecord')->find($identifier);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     */
    public function retrieveByToken($identifier, $token)
    {
        $staf = Staf::with('loginRecord')->find($identifier);

        if (!$staf) {
            return null;
        }

        $rememberToken = $staf->getRememberToken();

        return $rememberToken && hash_equals($rememberToken, $token) ? $staf : null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $user->save();
    }

    /**
     * Retrieve a user by the given credentials.
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) || 
            (count($credentials) === 1 && array_key_exists('password', $credentials))) {
            return null;
        }

        // Remove password from credentials for query
        $query = Staf::query();

        foreach ($credentials as $key => $value) {
            if ($key !== 'password') {
                $query->where($key, $value);
            }
        }

        $user = $query->first();
        
        // Explicitly load loginRecord relationship
        if ($user) {
            $user->load('loginRecord');
            
            \Log::info('User retrieved for authentication', [
                'no_kp' => $user->no_kp,
                'has_login_record' => !is_null($user->loginRecord),
                'login_record_id' => $user->loginRecord->id_login ?? 'N/A',
            ]);
        }

        return $user;
    }

    /**
     * Validate a user against the given credentials.
     * Password MUST come from login table ONLY
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        if (!isset($credentials['password'])) {
            \Log::error('No password in credentials');
            return false;
        }

        // Ensure loginRecord is loaded
        if (!$user->relationLoaded('loginRecord')) {
            $user->load('loginRecord');
        }

        // Get password from LOGIN TABLE ONLY
        if (!$user->loginRecord || !$user->loginRecord->password_hash) {
            \Log::error('No login record or password_hash for user', [
                'no_kp' => $user->no_kp,
                'id_staf' => $user->id_staf,
                'has_login_record' => !is_null($user->loginRecord),
            ]);
            return false;
        }

        $passwordHash = $user->loginRecord->password_hash;
        $result = Hash::check($credentials['password'], $passwordHash);
        
        \Log::info('Password validation from LOGIN table', [
            'no_kp' => $user->no_kp,
            'id_staf' => $user->id_staf,
            'id_login' => $user->loginRecord->id_login,
            'password_provided' => '***',
            'hash_first_20' => substr($passwordHash, 0, 20) . '...',
            'result' => $result ? 'SUCCESS' : 'FAILED',
        ]);

        return $result;
    }

    /**
     * Rehash the user's password if required and supported.
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
        if (!Hash::needsRehash($user->getAuthPassword()) && !$force) {
            return;
        }

        if ($user->loginRecord) {
            $user->loginRecord->update([
                'password_hash' => Hash::make($credentials['password']),
            ]);
        }
    }
}

