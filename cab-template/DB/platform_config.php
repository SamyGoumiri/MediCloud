<?php
return [
    // Enable or disable immediate sync to MediCloud platform
    'enabled' => true,
    // Platform database connection
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'medicloud',
    // Cabinet identifier in the platform DB (cabinets.id)
    'cabinet_id' => 1,
    // Optional source database label stored in feedbacks.source_database
    'source_database' => 'hippocare'
];
