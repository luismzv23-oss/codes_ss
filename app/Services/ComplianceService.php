<?php

namespace App\Services;

use App\Models\GeoAccessLogModel;
use App\Models\ResponsibleGamingLimitModel;
use App\Models\SystemSettingModel;
use App\Models\UserModel;

class ComplianceService
{
    public function validateUserCanOperate(int $userId, string $operation = 'operate'): array
    {
        $user = (new UserModel())->find($userId);
        if (! $user) {
            return $this->blocked('Usuario no encontrado.');
        }

        if (! $this->isAdult($user['birthdate'] ?? null)) {
            return $this->blocked('Debes tener al menos 18 anos para operar.');
        }

        $geo = $this->validateJurisdiction($userId);
        if (! $geo['allowed']) {
            return $geo;
        }

        $limits = $this->limitsForUser($userId);
        if (! empty($limits['self_excluded_until']) && strtotime($limits['self_excluded_until']) > time()) {
            return $this->blocked('La cuenta esta autoexcluida hasta ' . date('d/m/Y H:i', strtotime($limits['self_excluded_until'])) . '.');
        }

        if (! empty($limits['session_limit_minutes']) && session()->get('login_at')) {
            $elapsedMinutes = (time() - (int) session()->get('login_at')) / 60;
            if ($elapsedMinutes > (int) $limits['session_limit_minutes']) {
                return $this->blocked('Limite de sesion alcanzado. Cierra sesion y vuelve mas tarde.');
            }
        }

        return ['allowed' => true, 'message' => 'OK'];
    }

    public function validateDeposit(int $userId, float $amount): array
    {
        $base = $this->validateUserCanOperate($userId, 'deposit');
        if (! $base['allowed']) {
            return $base;
        }

        $limits = $this->limitsForUser($userId);
        $db = \Config\Database::connect();
        $today = date('Y-m-d 00:00:00');
        $month = date('Y-m-01 00:00:00');

        $daily = $this->sumTransactions($db, $userId, 'deposit', $today);
        $monthly = $this->sumTransactions($db, $userId, 'deposit', $month);

        if (! empty($limits['daily_deposit_limit']) && ($daily + $amount) > (float) $limits['daily_deposit_limit']) {
            return $this->blocked('Limite diario de deposito superado.');
        }

        if (! empty($limits['monthly_deposit_limit']) && ($monthly + $amount) > (float) $limits['monthly_deposit_limit']) {
            return $this->blocked('Limite mensual de deposito superado.');
        }

        return ['allowed' => true, 'message' => 'OK'];
    }

    public function validateStake(int $userId, float $stake): array
    {
        $base = $this->validateUserCanOperate($userId, 'bet');
        if (! $base['allowed']) {
            return $base;
        }

        $limits = $this->limitsForUser($userId);
        $db = \Config\Database::connect();
        $today = date('Y-m-d 00:00:00');
        $month = date('Y-m-01 00:00:00');

        $dailyLoss = $this->netLossSince($db, $userId, $today);
        $monthlyLoss = $this->netLossSince($db, $userId, $month);

        if (! empty($limits['daily_loss_limit']) && ($dailyLoss + $stake) > (float) $limits['daily_loss_limit']) {
            return $this->blocked('Limite diario de perdida superado.');
        }

        if (! empty($limits['monthly_loss_limit']) && ($monthlyLoss + $stake) > (float) $limits['monthly_loss_limit']) {
            return $this->blocked('Limite mensual de perdida superado.');
        }

        return ['allowed' => true, 'message' => 'OK'];
    }

    public function updateLimits(int $userId, array $payload): bool
    {
        $model = new ResponsibleGamingLimitModel();
        $current = $model->where('user_id', $userId)->first();
        $data = [
            'user_id' => $userId,
            'daily_deposit_limit' => $this->nullableMoney($payload['daily_deposit_limit'] ?? null),
            'monthly_deposit_limit' => $this->nullableMoney($payload['monthly_deposit_limit'] ?? null),
            'daily_loss_limit' => $this->nullableMoney($payload['daily_loss_limit'] ?? null),
            'monthly_loss_limit' => $this->nullableMoney($payload['monthly_loss_limit'] ?? null),
            'session_limit_minutes' => $this->nullableInt($payload['session_limit_minutes'] ?? null),
        ];

        if ($current) {
            return $model->update((int) $current['id'], $data);
        }

        return (bool) $model->insert($data);
    }

    public function selfExclude(int $userId, int $days, string $reason = ''): bool
    {
        $days = max(1, min(3650, $days));
        $model = new ResponsibleGamingLimitModel();
        $current = $model->where('user_id', $userId)->first();
        $data = [
            'user_id' => $userId,
            'self_excluded_until' => date('Y-m-d H:i:s', strtotime('+' . $days . ' days')),
            'self_exclusion_reason' => mb_substr($reason, 0, 255),
        ];

        if ($current) {
            return $model->update((int) $current['id'], $data);
        }

        return (bool) $model->insert($data);
    }

    public function limitsForUser(int $userId): array
    {
        return (new ResponsibleGamingLimitModel())->where('user_id', $userId)->first() ?? [];
    }

    private function validateJurisdiction(?int $userId): array
    {
        $settings = (new SystemSettingModel())->getAllSettings();
        $allowedCountries = array_filter(array_map('trim', explode(',', strtoupper($settings['allowed_country_codes'] ?? 'AR'))));
        $headerCountry = strtoupper((string) (service('request')->getHeaderLine('CF-IPCountry') ?: service('request')->getHeaderLine('X-Country-Code')));
        $userCountry = '';

        if ($userId) {
            $user = (new UserModel())->find($userId);
            $userCountry = strtoupper((string) ($user['country'] ?? ''));
        }

        $country = $headerCountry !== '' && $headerCountry !== 'XX' ? $headerCountry : $userCountry;
        $allowed = $country !== '' && in_array($country, $allowedCountries, true);

        (new GeoAccessLogModel())->insert([
            'user_id' => $userId,
            'ip_address' => service('request')->getIPAddress(),
            'country_code' => $country ?: null,
            'allowed' => $allowed ? 1 : 0,
            'reason' => $allowed ? 'allowed' : 'outside_allowed_jurisdiction',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $allowed
            ? ['allowed' => true, 'message' => 'OK']
            : $this->blocked('Operacion no disponible para tu jurisdiccion.');
    }

    private function isAdult(?string $birthdate): bool
    {
        if (empty($birthdate)) {
            return false;
        }

        try {
            return (new \DateTime($birthdate))->diff(new \DateTime())->y >= 18;
        } catch (\Throwable) {
            return false;
        }
    }

    private function sumTransactions($db, int $userId, string $type, string $since): float
    {
        $row = $db->table('transactions t')
            ->select('COALESCE(SUM(t.amount), 0) as total', false)
            ->join('wallets w', 'w.id = t.wallet_id')
            ->where('w.user_id', $userId)
            ->where('t.type', $type)
            ->where('t.created_at >=', $since)
            ->get()
            ->getRowArray();

        return (float) ($row['total'] ?? 0);
    }

    private function netLossSince($db, int $userId, string $since): float
    {
        $row = $db->table('bet_slips')
            ->select("
                COALESCE(SUM(stake), 0)
                - COALESCE(SUM(CASE WHEN status = 'won' THEN potential_payout ELSE 0 END), 0)
                - COALESCE(SUM(CASE WHEN status = 'cashed_out' THEN COALESCE(cashout_value, 0) ELSE 0 END), 0)
                as loss
            ", false)
            ->where('user_id', $userId)
            ->where('created_at >=', $since)
            ->get()
            ->getRowArray();

        return max(0, (float) ($row['loss'] ?? 0));
    }

    private function nullableMoney($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = round((float) $value, 2);
        return $value > 0 ? $value : null;
    }

    private function nullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (int) $value;
        return $value > 0 ? $value : null;
    }

    private function blocked(string $message): array
    {
        return ['allowed' => false, 'message' => $message];
    }
}
