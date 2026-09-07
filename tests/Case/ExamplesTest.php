<?php

declare(strict_types=1);

namespace Kumwe\Sequence\Tests\Case;

use Kumwe\Sequence\Tests\TestCase;

/**
 * Proves the shipped example is runnable and truthful.
 *
 * @since  0.1.0
 */
final class ExamplesTest extends TestCase
{
    /**
     * An explicit missing consumer autoloader must fail instead of silently loading the source checkout.
     *
     * @return  void
     *
     * @since   0.2.0
     */
    public function testAnExplicitMissingConsumerAutoloaderFailsClosed(): void
    {
        $missing = $this->root() . '/tests/nonexistent-consumer-autoload.php';
        $example = $this->runScript(['examples/allocate-and-render.php', $missing]);
        $smoke = $this->runScript(['resources/toolchain/autoload-smoke.php', $missing]);

        $this->assertSame(1, $example['status'], 'The example must not fall back to its source loader.');
        $this->assertSame(1, $smoke['status'], 'The smoke must require the specified consumer autoloader.');
        $this->assertStringContains('autoloader is missing', $example['output'], 'The missing consumer is reported.');
    }

    /**
     * The example allocates and renders against an in-memory reference with the exact output its README describes.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheExampleAllocatesAndRendersDeterministically(): void
    {
        $result = $this->runScript(['examples/allocate-and-render.php']);

        $this->assertSame(0, $result['status'], "The example must run.\n" . $result['output']);
        $this->assertSame(
            implode("\n", [
                'INV-2026-000001',
                'INV-2026-000002',
                'INV-2027-000001',
                'BR-0001',
                'BR-0001',
                'BR-0002',
                'refused: The number sequence counter is temporarily unavailable; replay the allocation.',
                'INV-2027-000002',
            ]),
            $result['output'],
            'The example output is deterministic and matches its README.',
        );
    }

    /**
     * The example types against the port and the values, never against a driver or the host.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheExampleTypesAgainstThePortAlone(): void
    {
        $code = $this->read('examples/allocate-and-render.php');

        $this->assertStringContains('implements NumberSequenceAllocator', $code, 'The reference implements the port.');
        $this->assertStringContains('NumberSequenceFormat::fromConfiguration', $code, 'The format is declared.');
        $this->assertStringContains('catch (NumberSequenceUnavailable', $code, 'The one refusal is caught by type.');
        $this->assertStringExcludes('Doctrine', $code, 'No driver.');
        $this->assertStringExcludes('Kumwe\\App', $code, 'No host.');
    }
}
