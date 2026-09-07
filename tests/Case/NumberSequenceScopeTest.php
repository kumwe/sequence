<?php

declare(strict_types=1);

namespace Kumwe\Sequence\Tests\Case;

use InvalidArgumentException;
use Kumwe\Sequence\Tests\TestCase;
use Kumwe\Sequence\Value\NumberSequenceScope;

/**
 * Pins the scope vocabulary, the closed key grammar and the equality and serialization of the cases.
 *
 * @since  0.1.0
 */
final class NumberSequenceScopeTest extends TestCase
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
            ['site', 'organization'],
            array_map(static fn (NumberSequenceScope $scope): string => $scope->value, NumberSequenceScope::cases()),
            'The backed case set is pinned declaration grammar.',
        );
        $this->assertSame(null, NumberSequenceScope::tryFrom('installation'), 'There is no installation-wide run.');
    }

    /**
     * A site-wide counter is keyed `-` whatever organization the record carries.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testASiteWideCounterIsKeyedByTheFixedMarker(): void
    {
        foreach ([null, '', 'north-branch'] as $organization) {
            $this->assertSame('-', NumberSequenceScope::Site->key($organization), 'The site key is fixed.');
        }
    }

    /**
     * A per-organization counter is keyed by the organization identifier exactly as resolved.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testAPerOrganizationCounterIsKeyedByTheOrganizationIdentifier(): void
    {
        foreach (['north-branch', 'HQ', '0191574f-f0b8-7bf3-a9aa-91c6b8244f02', 'branch 7'] as $identifier) {
            $this->assertSame(
                $identifier,
                NumberSequenceScope::Organization->key($identifier),
                'The identifier is the key, verbatim; its grammar is the host\'s.',
            );
        }
    }

    /**
     * A per-organization counter refuses an absent or empty organization rather than merging branches.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testAPerOrganizationCounterRefusesAnAbsentOrEmptyOrganization(): void
    {
        foreach ([null, ''] as $missing) {
            $refusal = $this->assertThrows(
                static fn (): string => NumberSequenceScope::Organization->key($missing),
                InvalidArgumentException::class,
                'An empty key would merge every branch into one run.',
            );
            $this->assertStringContains('carrying an organization', $refusal->getMessage(), 'The refusal says why.');
        }
    }

    /**
     * An organization cannot reuse the site marker and merge two different scope identities.
     *
     * @return  void
     *
     * @since   0.2.0
     */
    public function testAnOrganizationCannotUseTheReservedSiteCounterKey(): void
    {
        $refusal = $this->assertThrows(
            static fn (): string => NumberSequenceScope::Organization->key('-'),
            InvalidArgumentException::class,
            'The site-wide marker is reserved even when a host otherwise permits it as an identifier.',
        );

        $this->assertStringContains('reserved site-wide counter key', $refusal->getMessage(), 'Reserved marker.');
        $this->assertSame('-', NumberSequenceScope::Site->key('-'), 'The site scope retains its existing marker.');
    }

    /**
     * Cases are singletons: equality is identity, and a case round-trips through its backing value.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testCasesCompareByIdentityAndRoundTripThroughTheirValue(): void
    {
        $this->assertTrue(NumberSequenceScope::from('site') === NumberSequenceScope::Site, 'Identity.');
        foreach (NumberSequenceScope::cases() as $left) {
            foreach (NumberSequenceScope::cases() as $right) {
                $this->assertSame($left->value === $right->value, $left === $right, 'Identity follows the value.');
            }
        }
        $this->assertSame('organization', NumberSequenceScope::Organization->value, 'The backing value.');
        $this->assertSame('"site"', json_encode(NumberSequenceScope::Site), 'A case serializes as its value.');
        $this->assertSame(
            NumberSequenceScope::Organization,
            NumberSequenceScope::from(NumberSequenceScope::Organization->value),
            'Value to case and back.',
        );
    }
}
