<?php

declare(strict_types=1);

namespace Kumwe\Sequence\Tests\Case;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Kumwe\Sequence\Tests\TestCase;
use Kumwe\Sequence\Value\NumberSequenceReset;

/**
 * Pins the reset vocabulary and the period keys at their calendar boundaries in the declared zone.
 *
 * @since  0.1.0
 */
final class NumberSequenceResetTest extends TestCase
{
    /**
     * The backed case set is the published vocabulary and must not move.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheDeclaredVocabularyIsExactlyThePublishedCaseSet(): void
    {
        $this->assertSame(
            ['never', 'yearly', 'monthly', 'fiscal-period'],
            array_map(static fn (NumberSequenceReset $reset): string => $reset->value, NumberSequenceReset::cases()),
            'The backed case set is pinned declaration grammar.',
        );
        $this->assertSame(NumberSequenceReset::Yearly, NumberSequenceReset::from('yearly'), 'A value resolves.');
        $this->assertSame(null, NumberSequenceReset::tryFrom('quarterly'), 'An unknown value resolves to no case.');
        $this->assertSame('"monthly"', json_encode(NumberSequenceReset::Monthly), 'A case serializes as its value.');
    }

    /**
     * A lifetime run has an empty key at every instant and in every zone.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testALifetimeRunHasAnEmptyKeyEverywhere(): void
    {
        foreach (['UTC', 'Africa/Windhoek', 'Pacific/Kiritimati', 'America/Los_Angeles'] as $zone) {
            $this->assertSame(
                '',
                NumberSequenceReset::Never->key(new DateTimeImmutable('2026-12-31T23:59:59+00:00'), new DateTimeZone($zone)),
                'Never has no period segment in ' . $zone . '.',
            );
        }
    }

    /**
     * A yearly run rolls over at local midnight on 1 January, judged in the declared zone.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testAYearlyRunRollsOverAtLocalNewYear(): void
    {
        $windhoek = new DateTimeZone('Africa/Windhoek');
        $losAngeles = new DateTimeZone('America/Los_Angeles');

        $this->assertSame(
            '2026',
            NumberSequenceReset::Yearly->key(new DateTimeImmutable('2026-12-31T21:59:59+00:00'), $windhoek),
            'One second before local midnight is still the old year.',
        );
        $this->assertSame(
            '2027',
            NumberSequenceReset::Yearly->key(new DateTimeImmutable('2026-12-31T22:00:00+00:00'), $windhoek),
            'Local midnight starts the new year two hours before UTC does.',
        );
        $this->assertSame(
            '2026',
            NumberSequenceReset::Yearly->key(new DateTimeImmutable('2027-01-01T07:59:59+00:00'), $losAngeles),
            'West of Greenwich the old year lasts into UTC January.',
        );
    }

    /**
     * A monthly run rolls over at local midnight on the first of the month.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testAMonthlyRunRollsOverAtLocalMonthStart(): void
    {
        $utc = new DateTimeZone('UTC');
        $newYork = new DateTimeZone('America/New_York');

        $this->assertSame(
            '2026-01',
            NumberSequenceReset::Monthly->key(new DateTimeImmutable('2026-01-31T23:59:59+00:00'), $utc),
            'The last second of January.',
        );
        $this->assertSame(
            '2026-02',
            NumberSequenceReset::Monthly->key(new DateTimeImmutable('2026-02-01T00:00:00+00:00'), $utc),
            'The first second of February.',
        );
        $this->assertSame(
            '2026-01',
            NumberSequenceReset::Monthly->key(new DateTimeImmutable('2026-02-01T03:00:00+00:00'), $newYork),
            'Three hours into UTC February is still January in New York.',
        );
        $this->assertSame(
            '2026-12',
            NumberSequenceReset::Monthly->key(new DateTimeImmutable('2026-12-05T12:00:00+00:00'), $utc),
            'Months are zero-padded to two digits.',
        );
    }

    /**
     * The key depends on the instant and the declared zone only, never on the instant's own offset or the
     * process default zone.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheKeyIgnoresTheInstantsOffsetAndTheProcessDefaultZone(): void
    {
        $declared = new DateTimeZone('Africa/Windhoek');
        $asUtc = new DateTimeImmutable('2026-12-31T22:30:00+00:00');
        $asOffset = new DateTimeImmutable('2027-01-01T03:30:00+05:00');
        $previous = date_default_timezone_get();

        try {
            date_default_timezone_set('Pacific/Kiritimati');
            $this->assertSame('2027', NumberSequenceReset::Yearly->key($asUtc, $declared), 'Judged in the zone.');
            date_default_timezone_set('America/Los_Angeles');
            $this->assertSame('2027', NumberSequenceReset::Yearly->key($asUtc, $declared), 'Whatever the default.');
            $this->assertSame(
                NumberSequenceReset::Monthly->key($asUtc, $declared),
                NumberSequenceReset::Monthly->key($asOffset, $declared),
                'The same instant expressed in another offset keys the same counter.',
            );
        } finally {
            date_default_timezone_set($previous);
        }
    }

    /**
     * The fiscal case refuses the instant-and-timezone question instead of guessing a period key.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheFiscalPeriodKeyIsNeverDerivedFromAnInstantAndTimezone(): void
    {
        $refusal = $this->assertThrows(
            static fn (): string => NumberSequenceReset::FiscalPeriod->key(
                new DateTimeImmutable('2026-08-18T09:00:00+00:00'),
                new DateTimeZone('UTC'),
            ),
            InvalidArgumentException::class,
            'A fiscal period is a declared range, not a calendar formula.',
        );
        $this->assertStringContains('declared posting period', $refusal->getMessage(), 'The refusal says why.');
    }
}
