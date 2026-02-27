<?php

namespace Tests\Unit;

use App\Exceptions\FiscalSequenceExhaustedException;
use App\Exceptions\NoFiscalSequenceAvailableException;
use App\Models\FiscalSequence;
use App\Services\FiscalNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(FiscalNumberService::class);
});

test('getNextNcf returns first NCF from a new sequence', function () {
    // Create a fiscal sequence for B01 type
    FiscalSequence::create([
        'ncf_type' => 'B01',
        'series' => null,
        'ncf_from' => 'B01-00000000001',
        'ncf_to' => 'B01-00000001000',
        'current_ncf' => null,
        'valid_from' => now()->subDays(1),
        'valid_to' => now()->addYear(),
        'is_active' => true,
    ]);

    $ncf = $this->service->getNextNcf('B01');

    expect($ncf)->toBe('B01-00000000001');

    // Verify that current_ncf was updated
    $sequence = FiscalSequence::first();
    expect($sequence->current_ncf)->toBe('B01-00000000001');
});

test('getNextNcf increments NCF correctly on successive calls', function () {
    FiscalSequence::create([
        'ncf_type' => 'B01',
        'series' => null,
        'ncf_from' => 'B01-00000000001',
        'ncf_to' => 'B01-00000001000',
        'current_ncf' => null,
        'valid_from' => now()->subDays(1),
        'valid_to' => now()->addYear(),
        'is_active' => true,
    ]);

    $ncf1 = $this->service->getNextNcf('B01');
    $ncf2 = $this->service->getNextNcf('B01');
    $ncf3 = $this->service->getNextNcf('B01');

    expect($ncf1)->toBe('B01-00000000001');
    expect($ncf2)->toBe('B01-00000000002');
    expect($ncf3)->toBe('B01-00000000003');
});

test('getNextNcf handles different NCF types independently', function () {
    // Create sequences for different NCF types
    FiscalSequence::create([
        'ncf_type' => 'B01',
        'series' => null,
        'ncf_from' => 'B01-00000000001',
        'ncf_to' => 'B01-00000001000',
        'current_ncf' => null,
        'valid_from' => now()->subDays(1),
        'valid_to' => now()->addYear(),
        'is_active' => true,
    ]);

    FiscalSequence::create([
        'ncf_type' => 'B02',
        'series' => null,
        'ncf_from' => 'B02-00000000001',
        'ncf_to' => 'B02-00000001000',
        'current_ncf' => null,
        'valid_from' => now()->subDays(1),
        'valid_to' => now()->addYear(),
        'is_active' => true,
    ]);

    $ncfB01 = $this->service->getNextNcf('B01');
    $ncfB02 = $this->service->getNextNcf('B02');
    $ncfB01Second = $this->service->getNextNcf('B01');

    expect($ncfB01)->toBe('B01-00000000001');
    expect($ncfB02)->toBe('B02-00000000001');
    expect($ncfB01Second)->toBe('B01-00000000002');
});

test('getNextNcf handles series parameter correctly', function () {
    // Create sequences with different series
    FiscalSequence::create([
        'ncf_type' => 'B01',
        'series' => 'STORE-A',
        'ncf_from' => 'B01-00000000001',
        'ncf_to' => 'B01-00000001000',
        'current_ncf' => null,
        'valid_from' => now()->subDays(1),
        'valid_to' => now()->addYear(),
        'is_active' => true,
    ]);

    FiscalSequence::create([
        'ncf_type' => 'B01',
        'series' => 'STORE-B',
        'ncf_from' => 'B01-00000000001',
        'ncf_to' => 'B01-00000001000',
        'current_ncf' => null,
        'valid_from' => now()->subDays(1),
        'valid_to' => now()->addYear(),
        'is_active' => true,
    ]);

    $ncfStoreA = $this->service->getNextNcf('B01', 'STORE-A');
    $ncfStoreB = $this->service->getNextNcf('B01', 'STORE-B');
    $ncfStoreASecond = $this->service->getNextNcf('B01', 'STORE-A');

    expect($ncfStoreA)->toBe('B01-00000000001');
    expect($ncfStoreB)->toBe('B01-00000000001');
    expect($ncfStoreASecond)->toBe('B01-00000000002');
});

test('getNextNcf throws exception when no sequence exists', function () {
    $this->service->getNextNcf('B01');
})->throws(NoFiscalSequenceAvailableException::class);

test('getNextNcf throws exception when sequence is inactive', function () {
    FiscalSequence::create([
        'ncf_type' => 'B01',
        'series' => null,
        'ncf_from' => 'B01-00000000001',
        'ncf_to' => 'B01-00000001000',
        'current_ncf' => null,
        'valid_from' => now()->subDays(1),
        'valid_to' => now()->addYear(),
        'is_active' => false, // Inactive
    ]);

    $this->service->getNextNcf('B01');
})->throws(NoFiscalSequenceAvailableException::class);

test('getNextNcf throws exception when sequence is not yet valid', function () {
    FiscalSequence::create([
        'ncf_type' => 'B01',
        'series' => null,
        'ncf_from' => 'B01-00000000001',
        'ncf_to' => 'B01-00000001000',
        'current_ncf' => null,
        'valid_from' => now()->addDays(5), // Future date
        'valid_to' => now()->addYear(),
        'is_active' => true,
    ]);

    $this->service->getNextNcf('B01');
})->throws(NoFiscalSequenceAvailableException::class);

test('getNextNcf throws exception when sequence has expired', function () {
    FiscalSequence::create([
        'ncf_type' => 'B01',
        'series' => null,
        'ncf_from' => 'B01-00000000001',
        'ncf_to' => 'B01-00000001000',
        'current_ncf' => null,
        'valid_from' => now()->subYear(),
        'valid_to' => now()->subDays(1), // Expired yesterday
        'is_active' => true,
    ]);

    $this->service->getNextNcf('B01');
})->throws(NoFiscalSequenceAvailableException::class);

test('getNextNcf throws exception when range is exhausted', function () {
    FiscalSequence::create([
        'ncf_type' => 'B01',
        'series' => null,
        'ncf_from' => 'B01-00000000001',
        'ncf_to' => 'B01-00000000003', // Small range
        'current_ncf' => null,
        'valid_from' => now()->subDays(1),
        'valid_to' => now()->addYear(),
        'is_active' => true,
    ]);

    // Consume all NCFs
    $this->service->getNextNcf('B01'); // 001
    $this->service->getNextNcf('B01'); // 002
    $this->service->getNextNcf('B01'); // 003

    // This should throw because we've exhausted the range
    $this->service->getNextNcf('B01');
})->throws(FiscalSequenceExhaustedException::class);

test('getNextNcf handles NCF format without hyphen', function () {
    FiscalSequence::create([
        'ncf_type' => 'B01',
        'series' => null,
        'ncf_from' => 'B0100000000001',
        'ncf_to' => 'B0100000001000',
        'current_ncf' => null,
        'valid_from' => now()->subDays(1),
        'valid_to' => now()->addYear(),
        'is_active' => true,
    ]);

    $ncf1 = $this->service->getNextNcf('B01');
    $ncf2 = $this->service->getNextNcf('B01');

    expect($ncf1)->toBe('B0100000000001');
    expect($ncf2)->toBe('B0100000000002');
});

test('getNextNcf preserves NCF numeric width with padding', function () {
    FiscalSequence::create([
        'ncf_type' => 'B01',
        'series' => null,
        'ncf_from' => 'B01-00000000098',
        'ncf_to' => 'B01-00000000105',
        'current_ncf' => null,
        'valid_from' => now()->subDays(1),
        'valid_to' => now()->addYear(),
        'is_active' => true,
    ]);

    $ncf1 = $this->service->getNextNcf('B01');
    $ncf2 = $this->service->getNextNcf('B01');
    $ncf3 = $this->service->getNextNcf('B01');

    // Should maintain the 11-digit padding
    expect($ncf1)->toBe('B01-00000000098');
    expect($ncf2)->toBe('B01-00000000099');
    expect($ncf3)->toBe('B01-00000000100'); // Verify padding is maintained after crossing 99
});

test('previewNextNcf shows next NCF without consuming it', function () {
    FiscalSequence::create([
        'ncf_type' => 'B01',
        'series' => null,
        'ncf_from' => 'B01-00000000001',
        'ncf_to' => 'B01-00000001000',
        'current_ncf' => null,
        'valid_from' => now()->subDays(1),
        'valid_to' => now()->addYear(),
        'is_active' => true,
    ]);

    // Preview should return the next NCF
    $preview1 = $this->service->previewNextNcf('B01');
    expect($preview1)->toBe('B01-00000000001');

    // Preview again should show the same NCF
    $preview2 = $this->service->previewNextNcf('B01');
    expect($preview2)->toBe('B01-00000000001');

    // Now actually get the NCF
    $actual = $this->service->getNextNcf('B01');
    expect($actual)->toBe('B01-00000000001');

    // Preview should now show the next one
    $preview3 = $this->service->previewNextNcf('B01');
    expect($preview3)->toBe('B01-00000000002');
});

test('hasAvailableSequence returns true when valid sequence exists', function () {
    FiscalSequence::create([
        'ncf_type' => 'B01',
        'series' => null,
        'ncf_from' => 'B01-00000000001',
        'ncf_to' => 'B01-00000001000',
        'current_ncf' => null,
        'valid_from' => now()->subDays(1),
        'valid_to' => now()->addYear(),
        'is_active' => true,
    ]);

    expect($this->service->hasAvailableSequence('B01'))->toBeTrue();
});

test('hasAvailableSequence returns false when no sequence exists', function () {
    expect($this->service->hasAvailableSequence('B01'))->toBeFalse();
});

test('hasAvailableSequence returns false when sequence is exhausted', function () {
    FiscalSequence::create([
        'ncf_type' => 'B01',
        'series' => null,
        'ncf_from' => 'B01-00000000001',
        'ncf_to' => 'B01-00000000001', // Only one NCF
        'current_ncf' => 'B01-00000000001', // Already used
        'valid_from' => now()->subDays(1),
        'valid_to' => now()->addYear(),
        'is_active' => true,
    ]);

    expect($this->service->hasAvailableSequence('B01'))->toBeFalse();
});
