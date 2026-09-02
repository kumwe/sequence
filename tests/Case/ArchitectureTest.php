<?php

declare(strict_types=1);

namespace Kumwe\Sequence\Tests\Case;

use Kumwe\Sequence\Tests\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Proves the package boundary from the outside: no host, driver or container reaches the source tree, the
 * layers keep one dependency direction, the runtime requirement is PHP alone, and the release archive ships
 * what the Kumwe App adoption gate reads.
 *
 * @since  0.1.0
 */
final class ArchitectureTest extends TestCase
{
    /**
     * Paths the App adoption and clean-consumer gates read from the release archive.
     *
     * @var    list<string>
     * @since  0.1.0
     */
    private const array SHIPPED = [
        'CHANGELOG.md',
        'CHARTER.md',
        'LICENSE',
        'MIGRATION-HANDOFF.md',
        'README.md',
        'composer.json',
        'docs',
        'examples',
        'resources',
        'src',
    ];

    /**
     * Names that would tie the package to a host, a driver, a container or a framework.
     *
     * @var    list<string>
     * @since  0.1.0
     */
    private const array FORBIDDEN = [
        'Doctrine',
        'Kumwe\\App',
        'BusinessRecord',
        'BusinessDefinition',
        'Psr\\',
        'Laminas',
        'Mezzio',
        'PDO',
        'Symfony',
        'FOR UPDATE',
    ];

    /**
     * The architecture verifier accepts the source tree.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheArchitectureVerifierAcceptsTheSourceTree(): void
    {
        $result = $this->runScript(['tools/verify-architecture.php']);

        $this->assertSame(0, $result['status'], "The architecture verifier must pass.\n" . $result['output']);
        $this->assertStringContains('5 source files', $result['output'], 'Every source file was inspected.');
    }

    /**
     * No source file names a host, a driver, a container or a framework, in code or in documentation.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testNoSourceFileNamesAHostDriverContainerOrFramework(): void
    {
        foreach ($this->sourceFiles() as $relative => $code) {
            foreach (self::FORBIDDEN as $forbidden) {
                $this->assertStringExcludes($forbidden, $code, $relative . ' must stay storage-neutral.');
            }
            $this->assertStringContains('declare(strict_types=1);', $code, $relative . ' enables strict types.');
            $this->assertStringExcludes('@since  2.0.0', $code, $relative . ' records this package\'s own release.');
        }
    }

    /**
     * The layers keep one dependency direction: values stand alone, the contract may name its refusal, the
     * refusal names nothing.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheLayersKeepOneDependencyDirection(): void
    {
        foreach ($this->sourceFiles() as $relative => $code) {
            if (str_starts_with($relative, 'src/Value/')) {
                $this->assertStringExcludes('Kumwe\\Sequence\\Contract', $code, $relative . ' must not know the port.');
                $this->assertStringExcludes('Kumwe\\Sequence\\Exception', $code, $relative . ' refuses natively.');
            }
            if (str_starts_with($relative, 'src/Exception/')) {
                $this->assertStringExcludes('Kumwe\\Sequence\\Value', $code, $relative . ' names no value.');
                $this->assertStringExcludes('Kumwe\\Sequence\\Contract', $code, $relative . ' names no port.');
            }
            if (str_starts_with($relative, 'src/Contract/')) {
                $this->assertStringExcludes('Kumwe\\Sequence\\Value', $code, $relative . ' imports no value type.');
            }
        }
    }

    /**
     * The runtime requirement is PHP alone and the license is the repository's Apache-2.0.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheRuntimeRequirementIsPhpAlone(): void
    {
        $composer = $this->json('composer.json');
        $require = $composer['require'] ?? null;

        $this->assertSame('kumwe/sequence', $composer['name'] ?? null, 'The Composer coordinate.');
        $this->assertSame('Apache-2.0', $composer['license'] ?? null, 'The repository license is preserved.');
        $this->assertSame(['php'], is_array($require) ? array_keys($require) : null, 'PHP alone.');
        $this->assertSame('^8.5', is_array($require) ? ($require['php'] ?? null) : null, 'The supported range.');
        $this->assertSame(
            ['psr-4' => ['Kumwe\\Sequence\\' => 'src/']],
            $composer['autoload'] ?? null,
            'One canonical PSR-4 root and no alias root.',
        );
        $this->assertFalse(array_key_exists('version', $composer), 'The changelog is the release record.');
    }

    /**
     * The release archive ships every path the adoption gate reads and hides every development path.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheReleaseArchiveShipsWhatTheAdoptionGateReads(): void
    {
        $ignored = [];
        foreach (explode("\n", $this->read('.gitattributes')) as $line) {
            if (preg_match('#^/(\S+) export-ignore$#', trim($line), $match) === 1) {
                $ignored[] = $match[1];
            }
        }

        foreach (self::SHIPPED as $path) {
            $this->assertFalse(in_array($path, $ignored, true), $path . ' must ship in the release archive.');
        }
        foreach (['tests', 'tools', '.github', 'phpcs.xml', 'phpstan.neon', 'vendor', 'composer.lock'] as $path) {
            $this->assertTrue(in_array($path, $ignored, true), $path . ' must stay out of the release archive.');
        }
    }

    /**
     * Every source file with its repository-relative path.
     *
     * @return  array<string, string>  Path under the repository mapped to the file contents.
     *
     * @since   0.1.0
     */
    private function sourceFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root() . '/src', RecursiveDirectoryIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $relative = substr($file->getPathname(), strlen($this->root()) + 1);
                $files[$relative] = $this->read($relative);
            }
        }
        ksort($files);
        $this->assertSame(
            [
                'src/Contract/NumberSequenceAllocator.php',
                'src/Exception/NumberSequenceUnavailable.php',
                'src/Value/NumberSequenceFormat.php',
                'src/Value/NumberSequenceReset.php',
                'src/Value/NumberSequenceScope.php',
            ],
            array_keys($files),
            'The source tree holds exactly the five canonical files.',
        );

        return $files;
    }
}
