<?php

use App\Models\BetSelectionModel;
use App\Models\BetSlipModel;
use App\Models\EventModel;
use App\Models\LeagueModel;
use App\Models\MarketModel;
use App\Models\OddModel;
use App\Models\SportModel;
use App\Models\UserModel;
use App\Models\WalletModel;
use App\Services\SettlementService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class SettlementServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;

    private SettlementService $settlementService;
    private int $sportId;
    private int $leagueId;
    private int $userId;
    private int $walletId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settlementService = new SettlementService();

        $db = \Config\Database::connect();

        // Insert standard role if not exists
        $role = $db->table('roles')->where('id', 1)->get()->getRowArray();
        if (!$role) {
            $db->table('roles')->insert([
                'id' => 1,
                'name' => 'Bettor',
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Create Sport
        $sportModel = new SportModel();
        $this->sportId = $sportModel->insert([
            'name' => 'Fútbol',
            'slug' => 'futbol',
            'icon' => '⚽',
            'active' => 1
        ]);

        // Create League
        $leagueModel = new LeagueModel();
        $this->leagueId = $leagueModel->insert([
            'sport_id' => $this->sportId,
            'name'     => 'Liga Test',
            'country'  => 'Argentina',
            'active'   => 1
        ]);

        // Create User
        $userModel = new UserModel();
        $this->userId = $userModel->insert([
            'role_id' => 1,
            'username' => 'testuser_' . uniqid(),
            'email' => 'testuser_' . uniqid() . '@example.com',
            'password_hash' => 'password123',
            'is_active' => 1
        ]);

        // Create Wallet
        $walletModel = new WalletModel();
        $this->walletId = $walletModel->insert([
            'user_id' => $this->userId,
            'balance' => 1000.00,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function testSingleBetLostOnCancelledMatch(): void
    {
        // 1. Create Event (Status: cancelled, settled: 0)
        $eventModel = new EventModel();
        $eventId = $eventModel->insert([
            'league_id'   => $this->leagueId,
            'home_team'   => 'Team A',
            'away_team'   => 'Team B',
            'start_time'  => date('Y-m-d H:i:s', strtotime('+2 hours')),
            'status'      => 'cancelled',
            'score_home'  => null,
            'score_away'  => null,
            'settled'     => 0
        ]);

        // 2. Create Market & Odd
        $marketModel = new MarketModel();
        $marketId = $marketModel->insert([
            'event_id' => $eventId,
            'name'     => 'Ganador del Partido',
            'type'     => '1x2',
            'status'   => 'open'
        ]);

        $oddModel = new OddModel();
        $oddId = $oddModel->insert([
            'market_id'    => $marketId,
            'selection'    => '1',
            'odds_decimal' => 2.00,
            'active'       => 1
        ]);

        // 3. Create Bet Slip & Bet Selection
        $betSlipModel = new BetSlipModel();
        $slipId = $betSlipModel->insert([
            'user_id'          => $this->userId,
            'total_odds'       => 2.00,
            'stake'            => 100.00,
            'potential_payout' => 200.00,
            'status'           => 'pending',
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $betSelectionModel = new BetSelectionModel();
        $betSelectionModel->insert([
            'bet_slip_id'     => $slipId,
            'odd_id'          => $oddId,
            'odd_at_bet_time' => 2.00,
            'status'          => 'pending',
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        // Settle the cancelled event
        $event = $eventModel->find($eventId);
        $settled = $this->settlementService->settleCancelledEvent($event);

        $this->assertTrue($settled);

        // Assert selection is lost
        $selection = $betSelectionModel->where('bet_slip_id', $slipId)->first();
        $this->assertSame('lost', $selection['status']);

        // Assert slip is lost
        $slip = $betSlipModel->find($slipId);
        $this->assertSame('lost', $slip['status']);

        // Assert wallet balance remains the same (no refund)
        $wallet = (new WalletModel())->find($this->walletId);
        $this->assertEquals(1000.00, (float)$wallet['balance']);
    }

    public function testCombinedBetLostOnCancelledMatch(): void
    {
        // 1. Create Event 1 (Status: finished, scored)
        $eventModel = new EventModel();
        $eventId1 = $eventModel->insert([
            'league_id'   => $this->leagueId,
            'home_team'   => 'Team C',
            'away_team'   => 'Team D',
            'start_time'  => date('Y-m-d H:i:s', strtotime('-4 hours')),
            'status'      => 'finished',
            'score_home'  => 3,
            'score_away'  => 1,
            'settled'     => 1
        ]);

        $marketModel = new MarketModel();
        $marketId1 = $marketModel->insert([
            'event_id' => $eventId1,
            'name'     => 'Ganador del Partido',
            'type'     => '1x2',
            'status'   => 'closed'
        ]);

        $oddModel = new OddModel();
        $oddId1 = $oddModel->insert([
            'market_id'    => $marketId1,
            'selection'    => '1', // Home won
            'odds_decimal' => 1.50,
            'active'       => 0,
            'status'       => 'won'
        ]);

        // 2. Create Event 2 (Status: cancelled)
        $eventId2 = $eventModel->insert([
            'league_id'   => $this->leagueId,
            'home_team'   => 'Team E',
            'away_team'   => 'Team F',
            'start_time'  => date('Y-m-d H:i:s', strtotime('+2 hours')),
            'status'      => 'cancelled',
            'score_home'  => null,
            'score_away'  => null,
            'settled'     => 0
        ]);

        $marketId2 = $marketModel->insert([
            'event_id' => $eventId2,
            'name'     => 'Ganador del Partido',
            'type'     => '1x2',
            'status'   => 'open'
        ]);

        $oddId2 = $oddModel->insert([
            'market_id'    => $marketId2,
            'selection'    => '1',
            'odds_decimal' => 2.00,
            'active'       => 1
        ]);

        // 3. Create Combined Bet Slip (2 selections)
        $betSlipModel = new BetSlipModel();
        $slipId = $betSlipModel->insert([
            'user_id'          => $this->userId,
            'total_odds'       => 3.00,
            'stake'            => 100.00,
            'potential_payout' => 300.00,
            'status'           => 'pending',
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $betSelectionModel = new BetSelectionModel();
        // Selection 1: Won
        $betSelectionModel->insert([
            'bet_slip_id'     => $slipId,
            'odd_id'          => $oddId1,
            'odd_at_bet_time' => 1.50,
            'status'          => 'won',
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        // Selection 2: Pending (on the cancelled match)
        $selectionId2 = $betSelectionModel->insert([
            'bet_slip_id'     => $slipId,
            'odd_id'          => $oddId2,
            'odd_at_bet_time' => 2.00,
            'status'          => 'pending',
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        // Settle the cancelled event
        $event2 = $eventModel->find($eventId2);
        $settled = $this->settlementService->settleCancelledEvent($event2);

        $this->assertTrue($settled);

        // Assert the second selection is lost
        $selection2 = $betSelectionModel->find($selectionId2);
        $this->assertSame('lost', $selection2['status']);

        // Assert the combined slip is lost (even though selection 1 won)
        $slip = $betSlipModel->find($slipId);
        $this->assertSame('lost', $slip['status']);

        // Assert wallet balance remains the same (no refund)
        $wallet = (new WalletModel())->find($this->walletId);
        $this->assertEquals(1000.00, (float)$wallet['balance']);
    }
}
