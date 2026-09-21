<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'hold_minutes' => Env::int('BOOKING_HOLD_MINUTES', 15),
    'min_nights' => Env::int('BOOKING_MIN_NIGHTS', 1),
    'max_nights' => Env::int('BOOKING_MAX_NIGHTS', 60),
    'max_guests_per_room' => Env::int('BOOKING_MAX_GUESTS_PER_ROOM', 4),
    'advance_booking_days' => Env::int('BOOKING_ADVANCE_DAYS', 730),
    'require_email_verification' => Env::bool('BOOKING_REQUIRE_EMAIL_VERIFICATION', false),
    'instant_booking' => Env::bool('BOOKING_INSTANT', true),
    'allow_guest_booking' => Env::bool('BOOKING_ALLOW_GUEST', false),
    'reference_prefix' => Env::string('BOOKING_REFERENCE_PREFIX', 'SI'),

    // Booking state machine. Keys are the "from" state; values are legal destinations.
    'states' => [
        'DRAFT',
        'HOLD',
        'PENDING_PAYMENT',
        'PAYMENT_PROCESSING',
        'CONFIRMED',
        'CHECKED_IN',
        'COMPLETED',
        'CANCELLED',
        'EXPIRED',
        'REFUND_PENDING',
        'REFUNDED',
    ],
    'transitions' => [
        'DRAFT' => ['HOLD', 'PENDING_PAYMENT', 'CANCELLED', 'EXPIRED'],
        'HOLD' => ['PENDING_PAYMENT', 'CONFIRMED', 'EXPIRED', 'CANCELLED'],
        'PENDING_PAYMENT' => ['PAYMENT_PROCESSING', 'CONFIRMED', 'CANCELLED', 'EXPIRED'],
        'PAYMENT_PROCESSING' => ['CONFIRMED', 'CANCELLED', 'EXPIRED'],
        'CONFIRMED' => ['CHECKED_IN', 'CANCELLED', 'COMPLETED', 'REFUND_PENDING', 'REFUNDED'],
        'CHECKED_IN' => ['COMPLETED', 'CANCELLED'],
        'COMPLETED' => ['REFUND_PENDING'],
        'CANCELLED' => ['REFUND_PENDING', 'REFUNDED'],
        'EXPIRED' => [],
        'REFUND_PENDING' => ['REFUNDED', 'CANCELLED'],
        'REFUNDED' => [],
    ],

    // Cancellation policy presets (percentages are refundable share of the nightly subtotal + fees).
    'cancellation_policies' => [
        'flexible' => [
            'label' => 'Flexible',
            'description' => 'Full refund up to 24 hours before check-in.',
            'rules' => [
                ['days_before' => 1, 'refund_percent' => 100],
                ['days_before' => 0, 'refund_percent' => 50],
            ],
        ],
        'moderate' => [
            'label' => 'Moderate',
            'description' => 'Full refund up to 5 days before check-in, then 50%.',
            'rules' => [
                ['days_before' => 5, 'refund_percent' => 100],
                ['days_before' => 1, 'refund_percent' => 50],
                ['days_before' => 0, 'refund_percent' => 0],
            ],
        ],
        'strict' => [
            'label' => 'Strict',
            'description' => '50% refund up to 14 days before check-in. Non-refundable after that.',
            'rules' => [
                ['days_before' => 14, 'refund_percent' => 50],
                ['days_before' => 0, 'refund_percent' => 0],
            ],
        ],
        'non_refundable' => [
            'label' => 'Non-refundable',
            'description' => 'This rate cannot be refunded after confirmation.',
            'rules' => [
                ['days_before' => 0, 'refund_percent' => 0],
            ],
        ],
    ],
];