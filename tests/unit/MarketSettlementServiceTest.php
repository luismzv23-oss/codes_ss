<?php

use App\Services\MarketSettlementService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class MarketSettlementServiceTest extends CIUnitTestCase
{
    private MarketSettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MarketSettlementService();
    }

    public function testSettlesFootballMarketsFromFinalScore(): void
    {
        $event = [
            'home_team' => 'Mexico',
            'away_team' => 'Sudafrica',
            'score_home' => 2,
            'score_away' => 1,
        ];

        $winner = $this->service->settleMarket($event, ['type' => '1x2'], [
            ['id' => 1, 'selection' => 'Mexico'],
            ['id' => 2, 'selection' => 'Empate'],
            ['id' => 3, 'selection' => 'Sudafrica'],
        ]);

        $totals = $this->service->settleMarket($event, ['type' => 'totals'], [
            ['id' => 4, 'selection' => 'Over 2.5'],
            ['id' => 5, 'selection' => 'Under 2.5'],
        ]);

        $btts = $this->service->settleMarket($event, ['type' => 'btts'], [
            ['id' => 6, 'selection' => 'Si'],
            ['id' => 7, 'selection' => 'No'],
        ]);

        $this->assertSame([1 => 'won', 2 => 'lost', 3 => 'lost'], $winner['odds']);
        $this->assertSame([4 => 'won', 5 => 'lost'], $totals['odds']);
        $this->assertSame([6 => 'won', 7 => 'lost'], $btts['odds']);
    }

    public function testSkipsSettlementWithoutScore(): void
    {
        $result = $this->service->settleMarket(
            ['home_team' => 'A', 'away_team' => 'B', 'score_home' => null, 'score_away' => null],
            ['type' => '1x2'],
            [['id' => 1, 'selection' => 'A']]
        );

        $this->assertFalse($result['settled']);
    }
}
