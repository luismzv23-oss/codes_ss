<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSportsbookTables extends Migration
{
    public function up()
    {
        // 1. SPORTS
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => '100'],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => '100', 'unique' => true],
            'icon'       => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
            'active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('sports', true);

        // 2. LEAGUES
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sport_id'   => ['type' => 'INT', 'unsigned' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => '150'],
            'country'    => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('sport_id', 'sports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('leagues', true);

        // 3. EVENTS (Partidos)
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'league_id'  => ['type' => 'INT', 'unsigned' => true],
            'home_team'  => ['type' => 'VARCHAR', 'constraint' => '150'],
            'away_team'  => ['type' => 'VARCHAR', 'constraint' => '150'],
            'start_time' => ['type' => 'DATETIME'],
            'status'     => ['type' => 'ENUM', 'constraint' => ['pending', 'live', 'finished', 'cancelled'], 'default' => 'pending'],
            'score_home' => ['type' => 'INT', 'null' => true],
            'score_away' => ['type' => 'INT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('league_id', 'leagues', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('events', true);

        // 4. MARKETS (Tipos de apuesta: 1x2, O/U, Handicap)
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => '150'], // Ej. "Ganador del Partido"
            'type'       => ['type' => 'VARCHAR', 'constraint' => '50'],  // Ej. "1x2", "over_under"
            'status'     => ['type' => 'ENUM', 'constraint' => ['open', 'suspended', 'closed'], 'default' => 'open'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('event_id', 'events', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('markets', true);

        // 5. ODDS (Las cuotas específicas: Local, Empate, Visitante)
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'market_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'selection'    => ['type' => 'VARCHAR', 'constraint' => '100'], // Ej. "Home", "Draw", "Over 2.5"
            'odds_decimal' => ['type' => 'DECIMAL', 'constraint' => '8,3'], // Formato decimal (ej. 2.105)
            'active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('market_id', 'markets', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('odds', true);

        // 6. BET SLIPS (El ticket de apuesta del usuario)
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'unsigned' => true],
            'total_odds'       => ['type' => 'DECIMAL', 'constraint' => '10,3'],
            'stake'            => ['type' => 'DECIMAL', 'constraint' => '12,2'], // Monto apostado
            'potential_payout' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'status'           => ['type' => 'ENUM', 'constraint' => ['pending', 'won', 'lost', 'void', 'cashed_out'], 'default' => 'pending'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('bet_slips', true);

        // 7. BET SELECTIONS (Las líneas individuales dentro del ticket)
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'bet_slip_id'     => ['type' => 'BIGINT', 'unsigned' => true],
            'odd_id'          => ['type' => 'BIGINT', 'unsigned' => true],
            'odd_at_bet_time' => ['type' => 'DECIMAL', 'constraint' => '8,3'], // Para congelar la cuota
            'status'          => ['type' => 'ENUM', 'constraint' => ['pending', 'won', 'lost', 'void'], 'default' => 'pending'],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('bet_slip_id', 'bet_slips', 'id', 'CASCADE', 'CASCADE');
        // No hard foreign key on odd_id to allow deleting markets/odds if necessary without breaking historical bet history, 
        // but normally we don't delete odds. We'll add it for integrity.
        $this->forge->addForeignKey('odd_id', 'odds', 'id', 'RESTRICT', 'RESTRICT'); 
        $this->forge->createTable('bet_selections', true);
    }

    public function down()
    {
        $this->forge->dropTable('bet_selections', true);
        $this->forge->dropTable('bet_slips', true);
        $this->forge->dropTable('odds', true);
        $this->forge->dropTable('markets', true);
        $this->forge->dropTable('events', true);
        $this->forge->dropTable('leagues', true);
        $this->forge->dropTable('sports', true);
    }
}
