<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

if (!function_exists('encodeUrlx')) {
    function encodeUrlx($data): string
    {
        $cipher = env('OPENSSL_ENCRYPTION_ALGORITHM', 'aes-128-cbc');
        $key = bin2hex(env('APP_KEY', 'base64:rF2vjKpL9mQ7wXyZ3aBc5dEf8gH1iJ0kN4oPqRsTuVw='));

        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);

        global $tag;
        $cipherData = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);

        $encoded = base64_encode($iv . $cipherData);
        // Replacing some characters which may Result from Encoding
        $encodedModified = strtr($encoded, '+/=', '-_,');
        return $encodedModified;
    }
}

if (!function_exists('decodeUrlx')) {
    function decodeUrlx($data): false|string
    {
        // Check for Invalid Formats and data
        if ($data == null || strlen($data) <= 16) {
            redirect()->back();
        }

        $cipher = env('OPENSSL_ENCRYPTION_ALGORITHM', 'aes-128-cbc');
        $key = bin2hex(env('APP_KEY', 'base64:rF2vjKpL9mQ7wXyZ3aBc5dEf8gH1iJ0kN4oPqRsTuVw='));

        $encodedModified = strtr($data, '-_,', '+/=');
        $decoded = base64_decode($encodedModified);

        $encrypted = substr($decoded, 16);
        $iv = substr($decoded, 0, 16);

        $original_data = openssl_decrypt("$encrypted", $cipher, $key, OPENSSL_RAW_DATA, $iv);

        return $original_data;
    }
}

if (!function_exists('infoLogger')) {
    function infoLogger(string|null $path = null, string|null $message = null): void
    {
        $path = $path ?? 'info.log';
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/' . date('Y') . "/" . date('F') . "/" . date('Y-m-d') . "/" . getLoggedClass($trace) . '/' . getLoggedMethod($trace) . '/' . $path)
        ])->info($message);
    }
}

if (!function_exists('errorLogger')) {
    function errorLogger(string|null $path = null, string|null $message = null): void
    {
        $path = $path ?? 'error.log';
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/' . date('Y') . "/" . date('F') . "/" . date('Y-m-d') . "/" . getLoggedClass($trace) . '/' . getLoggedMethod($trace) . '/' . $path),
        ])->error($message);
    }
}

if (!function_exists('debugLogger')) {
    function debugLogger(string|null $path = null, string|null $message = null): void
    {
        $path = $path ?? 'debug.log';
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/' . date('Y') . "/" . date('F') . "/" . date('Y-m-d') . "/" . getLoggedClass($trace) . '/' . getLoggedMethod($trace) . '/' . $path),
        ])->debug($message);
    }
}

function getLoggedClass(array $trace)
{
    return \Illuminate\Support\Arr::last(explode("\\", $trace[1]['class'])) ?? null;
}

function getLoggedMethod(array $trace)
{
    return $trace[1]['function'] ?? null;
}

if (!function_exists('id')) {
    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    function id()
    {
        return authUser()->id;
    }
}

if (!function_exists('isMultidimensional')) {
    function isMultidimensional(array $array): bool
    {
        foreach ($array as $value) {
            if (is_array($value)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('getModule')) {
    /**
     * Get module by name
     * 
     * @param string $moduleName
     * @return \App\Models\Module|null
     */
    function getModule($moduleName)
    {
        return DB::table('modules')
            ->where('name', $moduleName)
            ->first();
    }
}

/**
 * Redact common sensitive fields from payload logs.
 */
if (!function_exists('redact')) {
    function redact(array $payload): array
    {
        $sensitive = ['password', 'secret', 'token', 'access_token', 'refresh_token'];

        foreach ($sensitive as $k) {
            if (array_key_exists($k, $payload)) {
                $payload[$k] = '***';
            }
        }

        return $payload;
    }
}

if (!function_exists('currentTenantId')) {
    /**
     * Resolve the active tenant from your JWT / tenant-scope layer.
     * Point this at wherever your Passport middleware stashes the tenant.
     */
    function currentTenantId(Request $request): ?string
    {
        return $request->attributes->get('tenant_id');
    }
}
