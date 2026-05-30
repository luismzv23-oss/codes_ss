<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\BetBuilderService;

/**
 * @internal
 */
final class BetBuilderTest extends CIUnitTestCase
{
    private BetBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BetBuilderService();
    }

    public function testIndependentEventsHaveNoDiscount(): void
    {
        $selections = [
            [
                'event_id' => 1,
                'market' => 'Ganador del Partido',
                'selection' => '1',
                'odds' => 2.00
            ],
            [
                'event_id' => 2,
                'market' => 'Ganador del Partido',
                'selection' => '2',
                'odds' => 3.00
            ]
        ];

        $result = $this->service->calculateCombinedOdds($selections);

        // Odds multiplier: 2.00 * 3.00 = 6.00
        $this->assertEquals(6.00, $result['odds']);
        $this->assertEquals(0.0, $result['discount']);
    }

    public function testCorrelatedSameEventAppliesDiscount(): void
    {
        $selections = [
            [
                'event_id' => 1,
                'market' => 'Ganador del Partido',
                'selection' => '1',
                'odds' => 2.50
            ],
            [
                'event_id' => 1,
                'market' => 'Total de Goles',
                'selection' => 'Over 2.5',
                'odds' => 2.00
            ]
        ];

        $result = $this->service->calculateCombinedOdds($selections);

        // Product: 2.50 * 2.00 = 5.00
        // Correlation between 1x2 and totals: 0.15
        // Combined odds: 5.00 * (1 - 0.15) = 4.25
        $this->assertEquals(4.25, $result['odds']);
        $this->assertEquals(0.15, $result['discount']);
    }

    public function testMutuallyExclusiveThrowsException(): void
    {
        $selections = [
            [
                'event_id' => 1,
                'market' => 'Ganador del Partido',
                'selection' => '1',
                'odds' => 2.50
            ],
            [
                'event_id' => 1,
                'market' => 'Ganador del Partido',
                'selection' => '2',
                'odds' => 3.00
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No se pueden combinar selecciones mutuamente excluyentes');

        $this->service->calculateCombinedOdds($selections);
    }
}
