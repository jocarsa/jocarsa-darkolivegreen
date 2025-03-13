<?php
// Eventos de calendario ficticios para demostración.
    $events = [
        ['id' => 1, 'title' => 'Team Meeting', 'date' => '2025-03-10', 'time' => '10:00 AM'],
        ['id' => 2, 'title' => 'Client Call', 'date' => '2025-03-11', 'time' => '2:00 PM'],
        ['id' => 3, 'title' => 'Project Deadline', 'date' => '2025-03-15', 'time' => 'All Day']
    ];
    echo json_encode($events);
?>
