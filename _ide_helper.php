<?php

/**
 * IDE Helper for CodeIgniter 4 & Intelephense / Static Analysis (Codex SS)
 */

namespace App\Controllers {
    /**
     * @property \CodeIgniter\HTTP\IncomingRequest $request
     * @property \CodeIgniter\HTTP\Response $response
     */
    abstract class BaseController extends \CodeIgniter\Controller {}
}

namespace App\Models {
    class UserModel extends \CodeIgniter\Model {}
    class WalletModel extends \CodeIgniter\Model {}
    class TransactionModel extends \CodeIgniter\Model {}
    class SportModel extends \CodeIgniter\Model {}
    class LeagueModel extends \CodeIgniter\Model {}
    class EventModel extends \CodeIgniter\Model {}
    class MarketModel extends \CodeIgniter\Model {}
    class OddModel extends \CodeIgniter\Model {}
    class BetSlipModel extends \CodeIgniter\Model {}
    class BetSelectionModel extends \CodeIgniter\Model {}
    class StagedEventModel extends \CodeIgniter\Model {}
    class KYCVerificationModel extends \CodeIgniter\Model {}
    class WithdrawalRequestModel extends \CodeIgniter\Model {}
}

namespace App\Services {
    class CashOutService {
        public function calculateCashOutValue(int $betSlipId): array { return []; }
        public function processPartialCashOut(int $betSlipId, int $userId, float $percentage): array { return []; }
    }
    class RiskControlService {
        public function triggerEmergencySuspend(int $eventId, string $reason = ''): bool { return true; }
        public function rebalanceOddsByHandle(int $marketId): array { return []; }
    }
    class OddsSyncService {
        public function detectPalpableError(float $importedOdds, float $previousOdds): bool { return false; }
    }
    class SettlementService {
        public function processPostponedEvents(int $maxHours = 24): int { return 0; }
        public function settleEventWithVerification(int $eventId, array $finalScores, string $verifier = 'system'): bool { return true; }
    }
}

namespace Config {
    class Services extends \CodeIgniter\Config\BaseService {}
}
