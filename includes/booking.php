<?php
/** Public URLs are real PHP entry points and also work without URL rewriting. */
function bookingRoutes(): array {
    return [
        'open-ground-hall' => 'open-ground-booking.php',
        'mini-hall' => 'mini-hall-booking.php',
        'luxury-rooms' => 'room-booking.php',
    ];
}

function bookingDefaults(): array {
    return [
        'open-ground-hall' => [
            'title' => 'Open Ground Venue',
            'intro' => 'A spacious open ground for weddings, receptions and other events in Kulur, Mangalore.',
            'notes' => 'Cleaning charges are an additional ₹1,500.',
            'packages' => [
                [
                    'name' => 'Open Ground Venue package', 'price' => 75000,
                    'facilities' => [
                        'Open ground for your event, with focus lighting throughout the venue.',
                        'Two security guards and a dedicated guest parking area.',
                        'Guest washrooms, a separate bride’s room and a separate groom’s room.',
                        'One additional family room accommodating approximately 10 to 12 people.',
                        'Swimming pool access for 20 hours. The remaining 4 hours are reserved for cleaning.',
                    ],
                ],
            ],
        ],
        'mini-hall' => [
            'title' => 'Mini Hall',
            'intro' => 'Choose the hall on its own or book the complete event package with decoration, sound and lighting.',
            'notes' => 'Cleaning charges are an additional ₹700. Extra facilities and arrangements are charged separately.',
            'packages' => [
                ['name' => 'Hall Only', 'price' => 25000, 'facilities' => ['Mini Hall hire with basic hall facilities.']],
                ['name' => 'Complete Event Package', 'price' => 50000, 'facilities' => ['Mini Hall hire.', 'Decoration.', 'Sound system.', 'Lighting arrangements.']],
            ],
        ],
        'luxury-rooms' => [
            'title' => 'Rooms',
            'intro' => 'Choose from three fully air-conditioned rooms, each with an attached washroom and 20 hours of swimming pool access.',
            'notes' => 'Additional guests are charged extra where applicable. Thank you for choosing Charles Gala Venue.',
            'packages' => [
                ['name' => 'Room No. 3 · Family Room', 'price' => 7000, 'facilities' => ['Accommodation for 10 people. Each additional guest above 10 costs ₹500.', 'Air conditioning and an attached washroom.', 'Swimming pool access for 20 hours.', 'Refrigerator, cooking facilities and vessels provided.']],
                ['name' => 'Room No. 2', 'price' => 6000, 'facilities' => ['Accommodation for up to 8 people.', 'Air conditioning and an attached washroom.', 'Swimming pool access for 20 hours.']],
                ['name' => 'Room No. 1', 'price' => 5000, 'facilities' => ['Accommodation for up to 5 people.', 'Air conditioning and an attached washroom.', 'Swimming pool access for 20 hours.']],
            ],
        ],
    ];
}

function bookingContent(string $key, array $settings): array {
    $default = bookingDefaults()[$key];
    $saved = json_decode($settings['booking_' . $key] ?? '', true);
    return is_array($saved) ? array_replace($default, $saved) : $default;
}

function bookingText($value, int $limit): string {
    if (!is_string($value) || trim($value) === '' || strlen($value) > $limit) {
        throw new RuntimeException('Complete each field and keep the text within its length limit.');
    }
    return trim($value);
}

function bookingFromForm(string $key, array $post): array {
    $page = bookingDefaults()[$key];
    foreach (['title'=>200, 'intro'=>2000, 'notes'=>2000] as $field=>$limit) {
        $page[$field] = bookingText($post[$field] ?? null, $limit);
    }
    foreach (['seo_title'=>250, 'seo_description'=>1000] as $field=>$limit) {
        $value = $post[$field] ?? '';
        $page[$field] = $value === '' ? '' : bookingText($value, $limit);
    }
    foreach ($page['packages'] as $i=>$package) {
        $row = $post['packages'][$i] ?? [];
        $price = filter_var($row['price'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>99999999]]);
        if ($price === false) throw new RuntimeException('Enter a positive whole-rupee price for each package.');
        $page['packages'][$i] = [
            'name' => bookingText($row['name'] ?? null, 200),
            'price' => $price,
            'facilities' => array_values(array_filter(array_map('trim', preg_split('/\R/u', bookingText($row['facilities'] ?? null, 4000))), 'strlen')),
        ];
    }
    return $page;
}
