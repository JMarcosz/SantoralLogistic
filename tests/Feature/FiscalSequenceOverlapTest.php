<?php

namespace Tests\Feature;

use App\Models\FiscalSequence;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('FiscalSequence Overlap Validation', function () {

    test('detects overlap when range is completely within another', function () {
        // Existing range: 001-020
        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000001',
            'ncf_to' => 'B01-00000000020',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        // Proposed range: 005-010 (completely inside)
        $hasOverlap = FiscalSequence::hasOverlap(
            'B01',
            null,
            'B01-00000000005',
            'B01-00000000010'
        );

        expect($hasOverlap)->toBeTrue();
    });

    test('detects overlap when range completely contains another', function () {
        // Existing range: 005-010
        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000005',
            'ncf_to' => 'B01-00000000010',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        // Proposed range: 001-020 (completely contains existing)
        $hasOverlap = FiscalSequence::hasOverlap(
            'B01',
            null,
            'B01-00000000001',
            'B01-00000000020'
        );

        expect($hasOverlap)->toBeTrue();
    });

    test('detects overlap when range starts inside and ends outside', function () {
        // Existing range: 001-020
        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000001',
            'ncf_to' => 'B01-00000000020',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        // Proposed range: 010-030 (starts inside, ends outside)
        $hasOverlap = FiscalSequence::hasOverlap(
            'B01',
            null,
            'B01-00000000010',
            'B01-00000000030'
        );

        expect($hasOverlap)->toBeTrue();
    });

    test('detects overlap when range ends inside and starts outside', function () {
        // Existing range: 010-030
        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000010',
            'ncf_to' => 'B01-00000000030',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        // Proposed range: 001-015 (starts outside, ends inside)
        $hasOverlap = FiscalSequence::hasOverlap(
            'B01',
            null,
            'B01-00000000001',
            'B01-00000000015'
        );

        expect($hasOverlap)->toBeTrue();
    });

    test('no overlap when range is completely before another', function () {
        // Existing range: 011-020
        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000011',
            'ncf_to' => 'B01-00000000020',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        // Proposed range: 001-010 (completely before)
        $hasOverlap = FiscalSequence::hasOverlap(
            'B01',
            null,
            'B01-00000000001',
            'B01-00000000010'
        );

        expect($hasOverlap)->toBeFalse();
    });

    test('no overlap when range is completely after another', function () {
        // Existing range: 001-010
        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000001',
            'ncf_to' => 'B01-00000000010',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        // Proposed range: 011-020 (completely after)
        $hasOverlap = FiscalSequence::hasOverlap(
            'B01',
            null,
            'B01-00000000011',
            'B01-00000000020'
        );

        expect($hasOverlap)->toBeFalse();
    });

    test('detects overlap when ranges touch at boundary (same NCF)', function () {
        // Existing range: 010-020
        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000010',
            'ncf_to' => 'B01-00000000020',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        // Proposed range: 001-010 (ends at same NCF where existing starts)
        $hasOverlap = FiscalSequence::hasOverlap(
            'B01',
            null,
            'B01-00000000001',
            'B01-00000000010'
        );

        expect($hasOverlap)->toBeTrue();
    });

    test('excludes current record when editing (no false positive)', function () {
        // Create existing range
        $sequence = FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000001',
            'ncf_to' => 'B01-00000000020',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        // Check overlap for the same range, excluding itself
        $hasOverlap = FiscalSequence::hasOverlap(
            'B01',
            null,
            'B01-00000000001',
            'B01-00000000020',
            $sequence->id  // Exclude this record
        );

        expect($hasOverlap)->toBeFalse();
    });

    test('detects overlap with other record when editing', function () {
        // Create two existing ranges
        $sequence1 = FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000001',
            'ncf_to' => 'B01-00000000020',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        $sequence2 = FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000030',
            'ncf_to' => 'B01-00000000050',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        // Try to edit sequence2 to overlap with sequence1
        $hasOverlap = FiscalSequence::hasOverlap(
            'B01',
            null,
            'B01-00000000015',  // Overlaps with sequence1
            'B01-00000000035',
            $sequence2->id  // Exclude sequence2 itself
        );

        expect($hasOverlap)->toBeTrue();
    });

    test('no overlap for different ncf_type', function () {
        // Existing range for B01
        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000001',
            'ncf_to' => 'B01-00000000020',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        // Proposed range for B02 (same range but different type)
        $hasOverlap = FiscalSequence::hasOverlap(
            'B02',
            null,
            'B02-00000000001',
            'B02-00000000020'
        );

        expect($hasOverlap)->toBeFalse();
    });

    test('no overlap for different series', function () {
        // Existing range for STORE-A
        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => 'STORE-A',
            'ncf_from' => 'B01-00000000001',
            'ncf_to' => 'B01-00000000020',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        // Proposed range for STORE-B (same range but different series)
        $hasOverlap = FiscalSequence::hasOverlap(
            'B01',
            'STORE-B',
            'B01-00000000001',
            'B01-00000000020'
        );

        expect($hasOverlap)->toBeFalse();
    });

    test('no overlap when existing sequence is inactive', function () {
        // Existing INACTIVE range
        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000001',
            'ncf_to' => 'B01-00000000020',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => false,  // Inactive
        ]);

        // Proposed range that would overlap if existing was active
        $hasOverlap = FiscalSequence::hasOverlap(
            'B01',
            null,
            'B01-00000000010',
            'B01-00000000030'
        );

        expect($hasOverlap)->toBeFalse();
    });

    test('detects overlap with any of multiple existing ranges', function () {
        // Create multiple existing ranges
        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000001',
            'ncf_to' => 'B01-00000000010',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000030',
            'ncf_to' => 'B01-00000000040',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000060',
            'ncf_to' => 'B01-00000000070',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        // Proposed range overlaps with the second range
        $hasOverlap = FiscalSequence::hasOverlap(
            'B01',
            null,
            'B01-00000000025',
            'B01-00000000035'
        );

        expect($hasOverlap)->toBeTrue();
    });

    test('handles null series correctly', function () {
        // Existing range with null series
        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000001',
            'ncf_to' => 'B01-00000000020',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        // Proposed range with null series (should detect overlap)
        $hasOverlap = FiscalSequence::hasOverlap(
            'B01',
            null,
            'B01-00000000010',
            'B01-00000000030'
        );

        expect($hasOverlap)->toBeTrue();
    });

    test('handles exact same range as overlap', function () {
        // Existing range
        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000001',
            'ncf_to' => 'B01-00000000020',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        // Proposed exact same range
        $hasOverlap = FiscalSequence::hasOverlap(
            'B01',
            null,
            'B01-00000000001',
            'B01-00000000020'
        );

        expect($hasOverlap)->toBeTrue();
    });
});
