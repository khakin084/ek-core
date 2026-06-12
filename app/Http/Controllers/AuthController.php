<?php

namespace App\Http\Controllers;

use App\Helpers\ServiceDiscoveryHelper;
use App\Models\User;
use Http;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;

class AuthController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function authenticateUser(Request $request): RedirectResponse
    {
        $hour = date('H');
        $this->validateLogin($request);

        try {
            $response = $this->attemptLogin($request);

            if ($response->failed()) {
                errorLogger('Auth server error' . json_encode([
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]));
            }

            $token = json_decode((string) $response->getBody(), true);
            $code = $response->getStatusCode();

            infoLogger("{$hour}" . ".info.log", json_encode($token));

            return $this->authenticationResponse($code, $token);

        } catch (\Exception $exception) {
            errorLogger("{$hour}" . ".error.log", json_encode([
                "message" => $exception->getMessage(),
                "trace" => $exception->getTrace()
            ]));
            return redirect()->back()->withErrors(['email' => "Something Went Wrong !!" ?? 'Unexpected error occurred'])->withInput();
        }
    }

    private function validateLogin(Request $request): void
    {
        $request->validate([
            'username' => "required",
            'password' => "required",
        ], [
            "username.required" => "Username is required",
            "password.required" => "Password is required"
        ]);
    }

    private function authenticationResponse(int $code, array $payload): RedirectResponse
    {
        if ($code === 200) {
            $token = $payload['token'] ?? null;
            $user = $payload['user'] ?? null;

            if (!$token || !$user) {
                return back()
                    ->withErrors(['username' => 'Authentication failed.'])
                    ->withInput();
            }

            Auth::setUser(new User($user));
            session(['access_token' => $token]);

            return redirect()->intended('home-page');
        }

        // Unauthorized -> go to login
        if ($code === 401) {
            return redirect()->route('login-index');
        }

        // Validation / server errors -> show message
        if (in_array($code, [422, 500], true)) {
            $message = $payload['message'] ?? 'Invalid credentials';

            return back()
                ->withErrors(['username' => $message])
                ->withInput();
        }

        // Fallback
        return back()
            ->withErrors(['username' => 'Unexpected error occurred.'])
            ->withInput();
    }
    
    private function attemptLogin(Request $request): \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
    {
        $auth_url = ServiceDiscoveryHelper::serviceUrl('ek-auth', '/api/v1/login');

        return Http::asForm()->post($auth_url, [
            'grant_type' => 'password',
            'client_id' => config('api.ek_auth_client_id'),
            'client_secret' => config('api.ek_auth_client_secret'),
            'username' => $request->username,
            'password' => $request->password,
            'channel' => 'web',
            'service_id' => config('api.current_system'),
        ]);
    }

    public function logout()
    {
        Session::flush();
        Auth::logout();

        return Redirect('login');
    }
}
