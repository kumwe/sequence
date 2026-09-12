<?php

declare(strict_types=1);

namespace Kumwe\Sequence\Tests\Case;

use Kumwe\Sequence\Tests\TestCase;

/**
 * Holds the three package manifests to the package's own claims and to the verifier that regenerates them.
 *
 * @since  0.1.0
 */
final class ManifestsTest extends TestCase
{
    /**
     * The canonical public symbols, and nothing else.
     *
     * @var    list<string>
     * @since  0.1.0
     */
    private const array SYMBOLS = [
        'Kumwe\\Sequence\\Contract\\NumberSequenceAllocator',
        'Kumwe\\Sequence\\Exception\\NumberSequenceUnavailable',
        'Kumwe\\Sequence\\Value\\NumberSequenceFormat',
        'Kumwe\\Sequence\\Value\\NumberSequenceReset',
        'Kumwe\\Sequence\\Value\\NumberSequenceScope',
    ];

    /**
     * The public API manifest pins exactly the five canonical symbols under the canonical namespace.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testThePublicApiManifestPinsExactlyTheCanonicalSymbols(): void
    {
        $manifest = $this->json('resources/public-api/v1.json');
        $symbols = $manifest['symbols'] ?? null;
        $names = is_array($symbols) ? array_keys($symbols) : [];
        sort($names);

        $this->assertSame('kumwe-package-public-api/v1', $manifest['schema'] ?? null, 'Version 2 schema.');
        $this->assertSame('kumwe/sequence', $manifest['package'] ?? null, 'The package coordinate is pinned.');
        $this->assertSame('Kumwe\\Sequence\\', $manifest['namespace'] ?? null, 'The canonical namespace.');
        $this->assertSame(self::SYMBOLS, $names, 'Exactly the five canonical symbols are exported.');
        $this->assertSame(
            ['Kumwe\\Sequence\\Contract\\NumberSequenceAllocator'],
            $manifest['extension_points'] ?? null,
            'The port is the one extension point; values and the refusal are final.',
        );
        $this->assertSame('src', $manifest['digest_of'] ?? null, 'The manifest digests the source tree.');
    }

    /**
     * The enum case sets and the format bounds are recorded surface, so a moved vocabulary fails the gate.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheEnumCasesAndFormatBoundsAreRecordedSurface(): void
    {
        $symbols = $this->json('resources/public-api/v1.json')['symbols'] ?? null;
        $symbols = is_array($symbols) ? $symbols : [];
        $reset = $symbols['Kumwe\\Sequence\\Value\\NumberSequenceReset'] ?? null;
        $scope = $symbols['Kumwe\\Sequence\\Value\\NumberSequenceScope'] ?? null;
        $format = $symbols['Kumwe\\Sequence\\Value\\NumberSequenceFormat'] ?? null;
        $constants = static fn (mixed $symbol): array => is_array($symbol) && is_array($symbol['constants'] ?? null)
            ? array_keys($symbol['constants'])
            : [];

        $this->assertSame('enum', is_array($reset) ? ($reset['kind'] ?? null) : null, 'The reset is an enum.');
        $this->assertSame(['FiscalPeriod', 'Monthly', 'Never', 'Yearly'], $constants($reset), 'Reset cases.');
        $this->assertSame(['Organization', 'Site'], $constants($scope), 'Scope cases.');
        $this->assertSame(['MAXIMUM_LENGTH', 'MAXIMUM_PADDING', 'MAXIMUM_PREFIX'], $constants($format), 'Bounds.');
        $this->assertSame(
            ['padding', 'prefix', 'reset', 'scope', 'timezone'],
            is_array($format) && is_array($format['properties'] ?? null) ? array_keys($format['properties']) : [],
            'The declaration coordinates are public read-only properties.',
        );
    }

    /**
     * The three manifests agree with each other and with the newest released changelog heading on the release.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheManifestsAgreeWithTheChangelogOnTheRelease(): void
    {
        $heading = null;
        $unreleased = false;
        foreach (explode("\n", $this->read('CHANGELOG.md')) as $line) {
            if (str_starts_with($line, '## ')) {
                if (!$unreleased && in_array($line, ['## Unreleased', '## [Unreleased]'], true)) {
                    $unreleased = true;
                    continue;
                }
                $heading = $line;
                break;
            }
        }
        $this->assertTrue(is_string($heading), 'The changelog carries a second-level heading.');
        $this->assertTrue(
            preg_match('/^## [0-9]+\.[0-9]+\.[0-9]+$/', (string) $heading) === 1,
            'The newest released changelog heading is the release-on-record version.',
        );
        $release = substr((string) $heading, 3);

        foreach (['public-api', 'capabilities', 'service-map'] as $manifest) {
            $this->assertSame(
                $release,
                $this->json('resources/' . $manifest . '/v1.json')['release'] ?? null,
                'resources/' . $manifest . '/v1.json records the changelog release.',
            );
        }
    }

    /**
     * Every capability names exported symbols, and together they claim every symbol exactly once.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testCapabilitiesClaimEverySymbolExactlyOnce(): void
    {
        $manifest = $this->json('resources/capabilities/v1.json');
        $capabilities = $manifest['capabilities'] ?? null;
        $byId = [];
        foreach (is_array($capabilities) ? $capabilities : [] as $capability) {
            if (is_array($capability) && is_string($capability['id'] ?? null)) {
                $byId[$capability['id']] = $capability;
            }
        }
        ksort($byId);

        $this->assertSame('kumwe-package-capabilities/v1', $manifest['schema'] ?? null, 'Version 2 schema.');
        $this->assertSame(
            ['sequence.allocation', 'sequence.format', 'sequence.reset', 'sequence.scope'],
            array_keys($byId),
            'The package declares exactly four capabilities.',
        );
        $this->assertSame(
            [
                'Kumwe\\Sequence\\Contract\\NumberSequenceAllocator',
                'Kumwe\\Sequence\\Exception\\NumberSequenceUnavailable',
            ],
            $byId['sequence.allocation']['symbols'] ?? null,
            'Allocation is the port and its one refusal.',
        );
        $this->assertSame(
            ['Kumwe\\Sequence\\Value\\NumberSequenceFormat'],
            $byId['sequence.format']['symbols'] ?? null,
            'The format capability is the declaration value alone.',
        );
        $this->assertSame(
            ['Kumwe\\Sequence\\Value\\NumberSequenceReset'],
            $byId['sequence.reset']['symbols'] ?? null,
            'The reset capability is the reset enum alone.',
        );
        $this->assertSame(
            ['Kumwe\\Sequence\\Value\\NumberSequenceScope'],
            $byId['sequence.scope']['symbols'] ?? null,
            'The scope capability is the scope enum alone.',
        );
        $description = $byId['sequence.allocation']['description'] ?? null;
        $this->assertStringContains(
            'no implementation',
            is_string($description) ? $description : '',
            'The allocation capability says the host implements the port.',
        );
        $this->assertTrue(
            array_key_exists('native_requirements', $manifest) && $manifest['native_requirements'] === null,
            'No native extension is required.',
        );
        $this->assertSame([], $manifest['deprecations'] ?? null, 'Nothing is deprecated in the first release.');
    }

    /**
     * The service map declares no provider, no factory and no alias, and records why.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheServiceMapDeclaresNoProviderWithAReason(): void
    {
        $manifest = $this->json('resources/service-map/v1.json');
        $reason = $manifest['provider_absence_reason'] ?? null;
        $reason = is_string($reason) ? trim($reason) : '';

        $this->assertSame('kumwe-package-service-map/v1', $manifest['schema'] ?? null, 'Version 2 schema.');
        $this->assertTrue(array_key_exists('config_provider', $manifest), 'The provider decision is recorded.');
        $this->assertSame(null, $manifest['config_provider'], 'A value and port package ships no ConfigProvider.');
        $this->assertTrue($reason !== '', 'The absence carries its reason.');
        $this->assertStringContains('binds', $reason, 'The reason says the host binds its adapter.');
        $this->assertSame([], $manifest['factories'] ?? null, 'No factory.');
        $this->assertSame([], $manifest['aliases'] ?? null, 'No alias.');
        $this->assertSame([], $manifest['delegators'] ?? null, 'No delegator.');
        $this->assertSame([], $manifest['configuration_keys'] ?? null, 'Nothing to configure.');
    }

    /**
     * The verifier regenerates the public API manifest byte for byte and accepts the hand-authored manifests.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheVerifierAcceptsTheRecordedManifests(): void
    {
        $result = $this->runScript(['tools/verify-manifests.php']);

        $this->assertSame(0, $result['status'], "The manifest verifier must accept the record.\n" . $result['output']);
        $this->assertStringContains('5 public symbols', $result['output'], 'The full surface is verified.');
        $this->assertStringContains('4 capabilities', $result['output'], 'Every capability is verified.');
        $this->assertStringContains('no provider (reason recorded)', $result['output'], 'The DI decision is verified.');
    }

    /**
     * No manifest carries a host, driver or historical name.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheManifestsCarryNoHostDriverOrHistoricalName(): void
    {
        foreach (['public-api', 'capabilities', 'service-map'] as $manifest) {
            $bytes = $this->read('resources/' . $manifest . '/v1.json');
            $this->assertStringExcludes('Kumwe\\\\App\\\\', $bytes, 'No App namespace survives extraction.');
            $this->assertStringExcludes('BusinessNumberSequenceAllocator', $bytes, 'No historical name.');
            $this->assertStringExcludes('Doctrine', $bytes, 'No driver is named by a manifest.');
        }
    }
}
