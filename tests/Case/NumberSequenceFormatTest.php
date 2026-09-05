<?php

declare(strict_types=1);

namespace Kumwe\Sequence\Tests\Case;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Kumwe\Sequence\Tests\TestCase;
use Kumwe\Sequence\Value\NumberSequenceFormat;
use Kumwe\Sequence\Value\NumberSequenceReset;
use Kumwe\Sequence\Value\NumberSequenceScope;
use ReflectionClass;

/**
 * Pins the declaration grammar, its bounds, the counter coordinates it composes and the deterministic
 * rendering of an allocated value — the behaviour extracted from Kumwe App, unchanged.
 *
 * @since  0.1.0
 */
final class NumberSequenceFormatTest extends TestCase
{
    /**
     * An empty declaration is a site-wide lifetime counter of six digits with no prefix, judged in UTC.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testAnEmptyDeclarationIsASiteWideLifetimeCounterInUtc(): void
    {
        $format = NumberSequenceFormat::fromConfiguration([]);

        $this->assertSame(NumberSequenceScope::Site, $format->scope, 'Default scope.');
        $this->assertSame(NumberSequenceReset::Never, $format->reset, 'Default reset.');
        $this->assertSame('', $format->prefix, 'Default prefix.');
        $this->assertSame(6, $format->padding, 'Default padding.');
        $this->assertSame('UTC', $format->timezone->getName(), 'Default zone.');
        $this->assertSame(
            ['scope' => '-', 'period' => ''],
            $format->counter('acme', new DateTimeImmutable('2026-12-31T23:30:00+00:00')),
            'A site-wide lifetime counter ignores the organization and the instant.',
        );
        $this->assertSame('000042', $format->render(42, ''), 'Six zero-padded digits and nothing else.');
    }

    /**
     * Null configuration values fall back to the defaults exactly as absent keys do.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testNullConfigurationValuesFallBackToTheDefaults(): void
    {
        $format = NumberSequenceFormat::fromConfiguration([
            'scope' => null,
            'reset' => null,
            'prefix' => null,
            'padding' => null,
            'timezone' => null,
        ]);

        $this->assertSame(NumberSequenceScope::Site, $format->scope, 'Null scope defaults.');
        $this->assertSame(NumberSequenceReset::Never, $format->reset, 'Null reset defaults.');
        $this->assertSame('', $format->prefix, 'Null prefix defaults.');
        $this->assertSame(6, $format->padding, 'Null padding defaults.');
        $this->assertSame('UTC', $format->timezone->getName(), 'Null zone defaults.');
    }

    /**
     * The reset period is judged in the declared zone, so a local new year starts a new run early in UTC.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheResetPeriodIsJudgedInTheDeclaredZoneRatherThanUtc(): void
    {
        $format = NumberSequenceFormat::fromConfiguration([
            'reset' => 'yearly',
            'prefix' => 'INV-',
            'padding' => 5,
            'timezone' => 'Africa/Windhoek',
        ]);
        $justBeforeMidnightLocal = new DateTimeImmutable('2026-12-31T21:30:00+00:00');
        $justAfterMidnightLocal = new DateTimeImmutable('2026-12-31T22:30:00+00:00');

        $this->assertSame('2026', $format->counter(null, $justBeforeMidnightLocal)['period'], 'Still 2026 locally.');
        $this->assertSame(
            '2027',
            $format->counter(null, $justAfterMidnightLocal)['period'],
            'A local new year starts a new run even while UTC is still in the old one.',
        );
        $this->assertSame('INV-2027-00001', $format->render(1, '2027'), 'Prefix, period, hyphen, digits.');
    }

    /**
     * A monthly run keys and renders its calendar month.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testAMonthlyRunKeysAndRendersItsCalendarMonth(): void
    {
        $format = NumberSequenceFormat::fromConfiguration(['reset' => 'monthly', 'prefix' => 'DN/', 'padding' => 3]);

        $this->assertSame(
            '2026-02',
            $format->counter(null, new DateTimeImmutable('2026-02-14T00:00:00+00:00'))['period'],
            'The month key is YYYY-MM.',
        );
        $this->assertSame('DN/2026-02-007', $format->render(7, '2026-02'), 'A slash prefix renders verbatim.');
    }

    /**
     * A per-organization run keys on the record's own organization and refuses to key on nothing.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testAPerOrganizationRunKeysOnTheRecordsOwnOrganization(): void
    {
        $format = NumberSequenceFormat::fromConfiguration(['scope' => 'organization']);

        $this->assertSame(
            'north-branch',
            $format->counter('north-branch', new DateTimeImmutable('2026-01-01T00:00:00+00:00'))['scope'],
            'The organization identifier is the scope key.',
        );
        $this->assertThrows(
            static fn (): array => $format->counter(null, new DateTimeImmutable('2026-01-01T00:00:00+00:00')),
            InvalidArgumentException::class,
            'A per-organization sequence without an organization has nothing to key on.',
        );
    }

    /**
     * The widest format still fits the column width the format itself declares, exactly.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheWidestFormatStillFitsTheColumnTheFormatDeclares(): void
    {
        $format = NumberSequenceFormat::fromConfiguration([
            'reset' => 'monthly',
            'prefix' => str_repeat('X', NumberSequenceFormat::MAXIMUM_PREFIX),
            'padding' => NumberSequenceFormat::MAXIMUM_PADDING,
        ]);
        $widest = $format->render(999_999_999_999, '2026-12');

        $this->assertSame(NumberSequenceFormat::MAXIMUM_LENGTH, strlen($widest), 'The widest number fills the column.');
    }

    /**
     * The bounds are the documented ones, and the maximum length is their exact sum.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheBoundsAreTheDocumentedOnes(): void
    {
        $this->assertSame(12, NumberSequenceFormat::MAXIMUM_PADDING, 'Twelve digits fit a signed 64-bit value.');
        $this->assertSame(16, NumberSequenceFormat::MAXIMUM_PREFIX, 'Sixteen prefix characters.');
        $this->assertSame(
            36,
            NumberSequenceFormat::MAXIMUM_LENGTH,
            'Prefix plus the widest calendar segment with its separator (8) plus the widest padding.',
        );
    }

    /**
     * A number that outgrew its padding is refused rather than truncated.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testANumberThatOutgrewItsPaddingIsRefusedRatherThanTruncated(): void
    {
        $format = NumberSequenceFormat::fromConfiguration([
            'reset' => 'monthly',
            'prefix' => str_repeat('X', NumberSequenceFormat::MAXIMUM_PREFIX),
            'padding' => NumberSequenceFormat::MAXIMUM_PADDING,
        ]);

        $refusal = $this->assertThrows(
            static fn (): string => $format->render(1_000_000_000_000, '2026-12'),
            InvalidArgumentException::class,
            'A thirteenth digit would exceed the column.',
        );
        $this->assertStringContains('outgrown', $refusal->getMessage(), 'The refusal names the cause.');
    }

    /**
     * A value wider than its padding is rendered in full when the column still holds it.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testAValueWiderThanItsPaddingIsRenderedInFullWhileTheColumnHoldsIt(): void
    {
        $format = NumberSequenceFormat::fromConfiguration(['padding' => 3]);

        $this->assertSame('001', $format->render(1, ''), 'Padded up to three digits.');
        $this->assertSame('1234', $format->render(1234, ''), 'Never truncated: padding is a minimum width.');
    }

    /**
     * Every unusable declaration is refused rather than defaulted over.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testAnUnusableDeclarationIsRefusedRatherThanDefaultedOver(): void
    {
        $unusable = [
            'unknown scope' => ['scope' => 'installation'],
            'unknown reset' => ['reset' => 'quarterly'],
            'unknown timezone' => ['timezone' => 'Mars/Olympus'],
            'lower-case prefix' => ['prefix' => 'inv-'],
            'prefix with a space' => ['prefix' => 'INV '],
            'prefix with an underscore' => ['prefix' => 'INV_'],
            'oversized prefix' => ['prefix' => str_repeat('X', NumberSequenceFormat::MAXIMUM_PREFIX + 1)],
            'padding below one' => ['padding' => 0],
            'negative padding' => ['padding' => -1],
            'padding above the ceiling' => ['padding' => NumberSequenceFormat::MAXIMUM_PADDING + 1],
            'padding as text' => ['padding' => '6'],
            'padding as a float' => ['padding' => 6.0],
            'scope as a number' => ['scope' => 1],
            'reset as a boolean' => ['reset' => true],
            'timezone as a number' => ['timezone' => 2],
        ];
        foreach ($unusable as $case => $configuration) {
            $this->assertThrows(
                static fn (): NumberSequenceFormat => NumberSequenceFormat::fromConfiguration($configuration),
                InvalidArgumentException::class,
                'The declaration "' . $case . '" must be refused.',
            );
        }
    }

    /**
     * The prefix grammar admits exactly upper-case letters, digits, hyphens and slashes, up to the bound.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testThePrefixGrammarAdmitsExactlyUpperCaseDigitsHyphenAndSlash(): void
    {
        $prefixes = ['', 'INV-', 'DN/', '2026/', 'A-B/C-9', str_repeat('Z', NumberSequenceFormat::MAXIMUM_PREFIX)];
        foreach ($prefixes as $prefix) {
            $this->assertSame(
                $prefix,
                NumberSequenceFormat::fromConfiguration(['prefix' => $prefix])->prefix,
                'The prefix is kept verbatim.',
            );
        }
    }

    /**
     * A zero or negative counter value is never rendered.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testAZeroOrNegativeCounterValueIsNeverRendered(): void
    {
        $format = NumberSequenceFormat::fromConfiguration([]);
        foreach ([0, -1, PHP_INT_MIN] as $value) {
            $this->assertThrows(
                static fn (): string => $format->render($value, ''),
                InvalidArgumentException::class,
                'An allocated value is always positive.',
            );
        }
    }

    /**
     * The key of a lifetime run is empty whatever the zone, and the renderer joins nothing for it.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheKeyOfALifetimeRunIsEmptyWhateverTheZone(): void
    {
        $this->assertSame(
            '',
            NumberSequenceReset::Never->key(
                new DateTimeImmutable('2026-06-15T12:00:00+00:00'),
                new DateTimeZone('Africa/Windhoek'),
            ),
            'A lifetime run has no period segment.',
        );
        $this->assertSame(
            'INV-000001',
            NumberSequenceFormat::fromConfiguration(['prefix' => 'INV-'])->render(1, ''),
            'No hyphen is inserted for an empty period key.',
        );
    }

    /**
     * A fiscal-period declaration parses, and renders a declared period key like any calendar segment.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testAFiscalPeriodDeclarationParsesAndRendersTheDeclaredKey(): void
    {
        $format = NumberSequenceFormat::fromConfiguration(['reset' => 'fiscal-period', 'prefix' => 'FIS-']);

        $this->assertSame(NumberSequenceReset::FiscalPeriod, $format->reset, 'The fiscal reset parses.');
        $this->assertSame('FIS-FY26.P08-000001', $format->render(1, 'FY26.P08'), 'A declared key renders verbatim.');
    }

    /**
     * A fiscal format refuses to compose a counter from the allocation instant.
     *
     * An empty or invented answer would silently merge every fiscal run into the lifetime counter, so
     * the refusal is plain: only the host's posting-period calendar can say which declared period a posting
     * date belongs to.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testAFiscalFormatRefusesToComposeACounterFromTheAllocationInstant(): void
    {
        $format = NumberSequenceFormat::fromConfiguration(['reset' => 'fiscal-period']);

        $refusal = $this->assertThrows(
            static fn (): array => $format->counter(null, new DateTimeImmutable('2026-08-18T09:00:00+00:00')),
            InvalidArgumentException::class,
            'The fiscal period key cannot come from an instant.',
        );
        $this->assertStringContains('declared posting period', $refusal->getMessage(), 'The refusal says why.');
    }

    /**
     * A declared fiscal key counts toward the maximum length exactly as a calendar segment would.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testADeclaredFiscalKeyCountsTowardTheMaximumLength(): void
    {
        $fits = NumberSequenceFormat::fromConfiguration([
            'reset' => 'fiscal-period',
            'prefix' => str_repeat('X', NumberSequenceFormat::MAXIMUM_PREFIX - 1),
            'padding' => NumberSequenceFormat::MAXIMUM_PADDING,
        ]);
        $overflows = NumberSequenceFormat::fromConfiguration([
            'reset' => 'fiscal-period',
            'prefix' => str_repeat('X', NumberSequenceFormat::MAXIMUM_PREFIX),
            'padding' => NumberSequenceFormat::MAXIMUM_PADDING,
        ]);

        $this->assertSame(
            NumberSequenceFormat::MAXIMUM_LENGTH,
            strlen($fits->render(1, 'FY26.P08')),
            'An eight-character declared key with a fifteen-character prefix fills the column exactly.',
        );
        $this->assertThrows(
            static fn (): string => $overflows->render(1, 'FY26.P08'),
            InvalidArgumentException::class,
            'One character more than the column holds is refused, not truncated.',
        );
    }

    /**
     * Rendering is a pure function of its arguments.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testRenderingIsDeterministic(): void
    {
        $format = NumberSequenceFormat::fromConfiguration(['reset' => 'yearly', 'prefix' => 'Q/', 'padding' => 4]);

        $this->assertSame($format->render(9, '2026'), $format->render(9, '2026'), 'Same input, same output.');
        $this->assertSame('Q/2026-0009', $format->render(9, '2026'), 'The rendered shape is fixed.');
        $this->assertSame(
            ['scope' => '-', 'period' => '2026'],
            $format->counter(null, new DateTimeImmutable('2026-03-01T00:00:00+00:00')),
            'The counter coordinates are keyed scope then period.',
        );
    }

    /**
     * The declaration is a final read-only value with public, read-only coordinates.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheDeclarationIsAFinalReadOnlyValue(): void
    {
        $reflection = new ReflectionClass(NumberSequenceFormat::class);
        $names = array_map(
            static fn (\ReflectionProperty $property): string => $property->getName(),
            $reflection->getProperties(\ReflectionProperty::IS_PUBLIC),
        );

        $this->assertTrue($reflection->isFinal(), 'The value is final.');
        $this->assertTrue($reflection->isReadOnly(), 'The value is read-only.');
        $this->assertSame(['scope', 'reset', 'prefix', 'padding', 'timezone'], $names, 'The public coordinates.');
        $constructor = $reflection->getConstructor();
        $this->assertFalse($constructor?->isPublic() ?? true, 'Construction goes through the factory.');
    }
}
