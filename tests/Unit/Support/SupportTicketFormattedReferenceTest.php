<?php

use App\Models\SupportTicket;

it('formats support ticket references with three leading zero padding', function () {
    expect(SupportTicket::formattedReference(1))->toBe('#0001')
        ->and(SupportTicket::formattedReference(42))->toBe('#00042')
        ->and(SupportTicket::formattedReference(1234))->toBe('#0001234');
});
