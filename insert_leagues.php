<?php
require 'public/index.php';

$db = \Config\Database::connect();
$sportId = $db->table('sports')->where('slug', 'football')->get()->getRow()->id ?? 1;

$leagues = [
    ['sport_id' => $sportId, 'name' => 'Mundial de la copa del mundo 2026', 'country' => 'Internacional', 'active' => 1],
    ['sport_id' => $sportId, 'name' => 'Copa Libertadores', 'country' => 'Sudamérica', 'active' => 1],
    ['sport_id' => $sportId, 'name' => 'Liga Profesional Argentina', 'country' => 'Argentina', 'active' => 1]
];

foreach ($leagues as $l) {
    $exists = $db->table('leagues')->where('name', $l['name'])->countAllResults();
    if ($exists == 0) {
        $db->table('leagues')->insert($l);
        $leagueId = $db->insertID();
        
        // Insert a dummy event so it has at least one
        $db->table('events')->insert([
            'league_id' => $leagueId,
            'home_team' => 'Equipo Local',
            'away_team' => 'Equipo Visitante',
            'start_time' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'status' => 'pending'
        ]);
    }
}
echo 'Leagues inserted successfully.';
