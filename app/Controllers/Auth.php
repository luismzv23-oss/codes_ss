<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Libraries\JWTAuth;
use GuzzleHttp\Client;

class Auth extends BaseController
{
    public function login()
    {
        // If already authenticated, redirect by role
        if (session()->get('isLoggedIn')) {
            return session()->get('role_id') == 1
                ? redirect()->to('/dashboard/overview')
                : redirect()->to('/');
        }
        
        return view('auth/login');
    }

    public function loginAction()
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[100]',
            'password' => 'required|min_length[4]|max_length[255]',
        ];

        if (!$this->validate($rules)) {
            if ($this->request->isAJAX() || strpos($this->request->getHeaderLine('HX-Request'), 'true') !== false) {
                return view('auth/login', [
                    'validation' => $this->validator,
                    'error' => 'Por favor, revise los datos ingresados.'
                ]);
            }
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userModel = new UserModel();
        $user = $userModel->where('username', $this->request->getPost('username'))
                          ->orWhere('email', $this->request->getPost('username'))
                          ->first();

        if (is_null($user) || !password_verify($this->request->getPost('password'), $user['password_hash'])) {
            if ($this->request->isAJAX() || strpos($this->request->getHeaderLine('HX-Request'), 'true') !== false) {
                return view('auth/login', ['error' => 'Credenciales inválidas.']);
            }
            return redirect()->back()->withInput()->with('error', 'Credenciales inválidas.');
        }

        if (!$user['is_active']) {
            if ($this->request->isAJAX() || strpos($this->request->getHeaderLine('HX-Request'), 'true') !== false) {
                return view('auth/login', ['error' => 'Cuenta inactiva.']);
            }
            return redirect()->back()->withInput()->with('error', 'Su cuenta está inactiva.');
        }

        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            $message = 'Cuenta bloqueada hasta ' . date('d/m/Y H:i', strtotime($user['locked_until'])) . '.';
            if ($this->request->isAJAX() || strpos($this->request->getHeaderLine('HX-Request'), 'true') !== false) {
                return view('auth/login', ['error' => $message]);
            }
            return redirect()->back()->withInput()->with('error', $message);
        }

        $this->startUserSession($user);

        if ($this->request->isAJAX() || strpos($this->request->getHeaderLine('HX-Request'), 'true') !== false) {
            // HTMX specific redirect
            $this->response->setHeader('HX-Redirect', ((int) $user['role_id'] === 1) ? '/dashboard/overview' : '/');
            return '';
        }

        return ((int) $user['role_id'] === 1)
            ? redirect()->to('/dashboard/overview')
            : redirect()->to('/');
    }

    public function google()
    {
        if (session()->get('isLoggedIn')) {
            return session()->get('role_id') == 1
                ? redirect()->to('/dashboard/overview')
                : redirect()->to('/');
        }

        $clientId = $this->googleClientId();
        if ($clientId === '' || $this->googleClientSecret() === '') {
            return redirect()->to('/auth/login')->with('error', 'Google Login no esta configurado. Defina GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET en .env.');
        }

        $state = bin2hex(random_bytes(32));
        session()->set('google_oauth_state', $state);

        $params = [
            'client_id'     => $clientId,
            'redirect_uri'  => $this->googleRedirectUri(),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ];

        return redirect()->to('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
    }

    public function googleCallback()
    {
        $state = (string) $this->request->getGet('state');
        $expectedState = (string) session()->get('google_oauth_state');
        session()->remove('google_oauth_state');

        if ($state === '' || $expectedState === '' || ! hash_equals($expectedState, $state)) {
            return redirect()->to('/auth/login')->with('error', 'No se pudo validar la sesion de Google. Intente nuevamente.');
        }

        $code = (string) $this->request->getGet('code');
        if ($code === '') {
            return redirect()->to('/auth/login')->with('error', 'Google no devolvio un codigo de autorizacion.');
        }

        try {
            $googleUser = $this->fetchGoogleUser($code);
        } catch (\Throwable $e) {
            log_message('error', 'Google OAuth error: ' . $e->getMessage());
            return redirect()->to('/auth/login')->with('error', 'No se pudo iniciar sesion con Google.');
        }

        if (empty($googleUser['email']) || empty($googleUser['sub'])) {
            return redirect()->to('/auth/login')->with('error', 'Google no devolvio un email valido para la cuenta.');
        }

        if (isset($googleUser['email_verified']) && ! filter_var($googleUser['email_verified'], FILTER_VALIDATE_BOOLEAN)) {
            return redirect()->to('/auth/login')->with('error', 'El email de Google no esta verificado.');
        }

        $userModel = new UserModel();
        $user = $userModel->where('google_id', $googleUser['sub'])->first();

        if (! $user) {
            $user = $userModel->where('email', $googleUser['email'])->first();
        }

        if ($user) {
            if (! $user['is_active']) {
                return redirect()->to('/auth/login')->with('error', 'Su cuenta esta inactiva.');
            }

            if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
                return redirect()->to('/auth/login')->with('error', 'Cuenta bloqueada hasta ' . date('d/m/Y H:i', strtotime($user['locked_until'])) . '.');
            }

            $userModel->update($user['id'], [
                'google_id'         => $googleUser['sub'],
                'oauth_provider'    => 'google',
                'email_verified_at' => $user['email_verified_at'] ?: date('Y-m-d H:i:s'),
                'last_login_at'     => date('Y-m-d H:i:s'),
                'last_login_ip'     => $this->request->getIPAddress(),
            ]);
            $user = $userModel->find($user['id']);
        } else {
            $userId = $userModel->insert([
                'role_id'                 => 2,
                'username'                => $this->makeUniqueGoogleUsername($googleUser),
                'email'                   => $googleUser['email'],
                'google_id'               => $googleUser['sub'],
                'oauth_provider'          => 'google',
                'password_hash'           => bin2hex(random_bytes(32)),
                'is_active'               => 1,
                'kyc_status'              => 'pending',
                'email_verified_at'       => date('Y-m-d H:i:s'),
                'email_verification_token'=> null,
                'email_verification_sent_at'=> null,
                'last_login_at'           => date('Y-m-d H:i:s'),
                'last_login_ip'           => $this->request->getIPAddress(),
            ]);
            $user = $userModel->find($userId);
        }

        $this->startUserSession($user);

        return ((int) $user['role_id'] === 1)
            ? redirect()->to('/dashboard/overview')
            : redirect()->to('/');
    }

    public function register()
    {
        // If already authenticated, redirect by role
        if (session()->get('isLoggedIn')) {
            return session()->get('role_id') == 1
                ? redirect()->to('/dashboard/overview')
                : redirect()->to('/');
        }
        
        return view('auth/register');
    }

    public function registerAction()
    {
        $rules = [
            'username'         => 'required|alpha_numeric_space|min_length[3]|max_length[100]|is_unique[users.username]',
            'email'            => 'required|valid_email|is_unique[users.email]',
            'phone_country'    => 'required|numeric|min_length[1]|max_length[4]',
            'phone_area'       => 'required|numeric|min_length[1]|max_length[5]',
            'phone_number'     => 'required|numeric|min_length[5]|max_length[12]',
            'country'          => 'required|exact_length[2]',
            'document_number'  => 'required|numeric|min_length[7]|max_length[11]',
            'birthdate'        => 'required|valid_date[Y-m-d]',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
            'privacy_policy'   => 'required'
        ];

        $errors = [
            'username' => [
                'required' => 'El nombre de usuario es obligatorio.',
                'alpha_numeric_space' => 'El usuario solo puede contener letras, números y espacios.',
                'min_length' => 'El usuario debe tener al menos 3 caracteres.',
                'max_length' => 'El usuario no puede superar los 100 caracteres.',
                'is_unique' => 'Este nombre de usuario ya está registrado.',
            ],
            'email' => [
                'required' => 'El correo electrónico es obligatorio.',
                'valid_email' => 'El formato del correo electrónico no es válido.',
                'is_unique' => 'Este correo electrónico ya está registrado.',
            ],
            'phone_country' => [
                'required' => 'El código de país del teléfono es obligatorio.',
                'numeric'  => 'El código de país del teléfono debe ser numérico.',
                'min_length' => 'El código de país debe tener al menos 1 dígito.',
                'max_length' => 'El código de país no puede superar los 4 dígitos.',
            ],
            'phone_area' => [
                'required' => 'El código de área del teléfono es obligatorio.',
                'numeric'  => 'El código de área del teléfono debe ser numérico.',
                'min_length' => 'El código de área debe tener al menos 1 dígito.',
                'max_length' => 'El código de área no puede superar los 5 dígitos.',
            ],
            'phone_number' => [
                'required' => 'El número de teléfono es obligatorio.',
                'numeric'  => 'El número de teléfono debe ser numérico.',
                'min_length' => 'El número de teléfono debe tener al menos 5 dígitos.',
                'max_length' => 'El número de teléfono no puede superar los 12 dígitos.',
            ],
            'country' => [
                'required' => 'La nacionalidad es obligatoria.',
                'exact_length' => 'La nacionalidad seleccionada es inválida.',
            ],
            'document_number' => [
                'required' => 'El número de documento DNI/CUIT es obligatorio.',
                'numeric'  => 'El número de documento debe contener solo números.',
                'min_length' => 'El documento debe tener al menos 7 dígitos.',
                'max_length' => 'El documento no puede superar los 11 dígitos.',
            ],
            'birthdate' => [
                'required' => 'La fecha de nacimiento es obligatoria.',
                'valid_date' => 'La fecha de nacimiento no es una fecha válida.',
            ],
            'password' => [
                'required' => 'La contraseña es obligatoria.',
                'min_length' => 'La contraseña debe tener al menos 8 caracteres.',
            ],
            'password_confirm' => [
                'required' => 'Debe confirmar su contraseña.',
                'matches' => 'Las contraseñas no coinciden.',
            ],
            'privacy_policy' => [
                'required' => 'Debe aceptar las políticas de privacidad y seguridad.',
            ],
        ];

        // Run general validation
        $isValid = $this->validate($rules, $errors);

        // Run age verification
        $ageValid = true;
        $birthdateStr = $this->request->getPost('birthdate');
        if (!empty($birthdateStr)) {
            try {
                $dob = new \DateTime($birthdateStr);
                $today = new \DateTime();
                $age = $dob->diff($today)->y;
                if ($age < 18) {
                    $ageValid = false;
                }
            } catch (\Throwable $t) {
                $ageValid = false;
            }
        }

        if (!$isValid || !$ageValid) {
            $validation = $this->validator ?? \Config\Services::validation();
            if (!$ageValid) {
                $validation->setError('birthdate', 'Debe tener al menos 18 años de edad para poder registrarse y apostar.');
            }

            if ($this->request->isAJAX() || strpos($this->request->getHeaderLine('HX-Request'), 'true') !== false) {
                return view('auth/register', [
                    'validation' => $validation,
                    'error' => 'Por favor, revise los datos ingresados.'
                ]);
            }
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $phoneCountry = $this->request->getPost('phone_country');
        $phoneArea = $this->request->getPost('phone_area');
        $phoneNumber = $this->request->getPost('phone_number');
        $combinedPhone = '+' . trim($phoneCountry) . ' ' . trim($phoneArea) . ' ' . trim($phoneNumber);
        $combinedPhone = substr($combinedPhone, 0, 20);

        $docNum = trim((string)$this->request->getPost('document_number'));
        $docType = strlen($docNum) === 11 ? 'CUIT' : 'DNI';

        $userModel = new UserModel();
        $data = [
            'username'         => $this->request->getPost('username'),
            'email'            => $this->request->getPost('email'),
            'password_hash'    => $this->request->getPost('password'),
            'phone'            => $combinedPhone,
            'country'          => $this->request->getPost('country'),
            'birthdate'        => $this->request->getPost('birthdate'),
            'document_type'    => $docType,
            'document_number'  => $docNum,
            'kyc_status'       => 'approved',
            'role_id'          => 2, // Standard user role
            'is_active'        => 1,
        ];

        // Generate email verification token (64 chars) and set sent timestamp
        $verificationToken = bin2hex(random_bytes(32));
        $data['email_verification_token'] = $verificationToken;
        $data['email_verification_sent_at'] = date('Y-m-d H:i:s');

        // Insert user
        $userModel->insert($data);

        // Enqueue verification email via existing 'emails' queue
        $queue = \Config\Services::queue();
        $queue->push('emails', \App\Jobs\SendVerificationEmailJob::class, [
            'to'   => $data['email'],
            'name' => $data['username'],
            'token'=> $verificationToken,
        ]);

        // Optionally, could send synchronously if needed
        // $this->response->setHeader('Location', '/auth/login');
        return redirect()->to('/');
    }

    public function verify(string $token)
    {
        $userModel = new \App\Models\UserModel();
        $user = $userModel->where('email_verification_token', $token)->first();
        if (!$user) {
            return view('auth/verify_error', ['message' => 'Enlace de verificación inválido o expirado.']);
        }
        // Check token age (24h)
        $sentAt = $user['email_verification_sent_at'];
        if ($sentAt) {
            $sent = new \DateTime($sentAt);
            $now = new \DateTime();
            $diff = $now->getTimestamp() - $sent->getTimestamp();
            if ($diff > 24 * 3600) {
                return view('auth/verify_error', ['message' => 'El enlace de verificación ha expirado.']);
            }
        }
        // Mark as verified
        $userModel->update($user['id'], [
            'email_verified_at'       => date('Y-m-d H:i:s'),
            'email_verification_token'=> null,
            'email_verification_sent_at'=> null,
        ]);
        return view('auth/verify_success');
    }

    public function logout()
    {
        session()->destroy();
        $this->response->deleteCookie('access_token');
        return redirect()->to('/');
    }

    private function startUserSession(array $user): void
    {
        $token = JWTAuth::generateToken($user);

        $this->response->setCookie(
            'access_token',
            $token,
            8 * 3600,
            '',
            '',
            '',
            false,
            true
        );

        session()->set([
            'user_id'    => $user['id'],
            'username'   => $user['username'],
            'role_id'    => $user['role_id'],
            'isLoggedIn' => true,
            'login_at'   => time(),
        ]);
    }

    private function fetchGoogleUser(string $code): array
    {
        $client = new Client(['timeout' => 10]);
        $tokenResponse = $client->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'code'          => $code,
                'client_id'     => $this->googleClientId(),
                'client_secret' => $this->googleClientSecret(),
                'redirect_uri'  => $this->googleRedirectUri(),
                'grant_type'    => 'authorization_code',
            ],
        ]);

        $tokenData = json_decode((string) $tokenResponse->getBody(), true);
        if (empty($tokenData['access_token'])) {
            throw new \RuntimeException('Google token response did not include access_token.');
        }

        $userResponse = $client->get('https://www.googleapis.com/oauth2/v3/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ' . $tokenData['access_token'],
            ],
        ]);

        return json_decode((string) $userResponse->getBody(), true) ?: [];
    }

    private function makeUniqueGoogleUsername(array $googleUser): string
    {
        $base = $googleUser['name'] ?? explode('@', $googleUser['email'])[0] ?? 'google_user';
        $base = strtolower(trim((string) $base));
        $base = preg_replace('/[^a-z0-9 ]+/', '', $base) ?: 'google_user';
        $base = trim(preg_replace('/\s+/', ' ', $base) ?? $base);
        $base = substr($base, 0, 80);

        $userModel = new UserModel();
        $username = $base;
        $suffix = 1;

        while ($userModel->where('username', $username)->first()) {
            $suffix++;
            $username = substr($base, 0, 75) . ' ' . $suffix;
        }

        return $username;
    }

    private function googleClientId(): string
    {
        return trim((string) (env('GOOGLE_CLIENT_ID') ?: getenv('GOOGLE_CLIENT_ID') ?: ''));
    }

    private function googleClientSecret(): string
    {
        return trim((string) (env('GOOGLE_CLIENT_SECRET') ?: getenv('GOOGLE_CLIENT_SECRET') ?: ''));
    }

    private function googleRedirectUri(): string
    {
        $configured = trim((string) (env('GOOGLE_REDIRECT_URI') ?: getenv('GOOGLE_REDIRECT_URI') ?: ''));
        return $configured !== '' ? $configured : base_url('auth/google/callback');
    }
}
