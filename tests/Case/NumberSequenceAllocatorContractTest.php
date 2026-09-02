<?php

declare(strict_types=1);

namespace Kumwe\Sequence\Tests\Case;

use DateTimeImmutable;
use Kumwe\Sequence\Contract\NumberSequenceAllocator;
use Kumwe\Sequence\Exception\NumberSequenceUnavailable;
use Kumwe\Sequence\Tests\TestCase;
use Kumwe\Sequence\Value\NumberSequenceFormat;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Pins the shape and the documented promises of the allocator port, and proves the port is implementable
 * without any host type by holding an in-memory reference to the same promises.
 *
 * @since  0.1.0
 */
final class NumberSequenceAllocatorContractTest extends TestCase
{
    /**
     * The port is an interface with exactly one method taking the five counter coordinates and the instant.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testThePortIsAnInterfaceWithOneAllocationMethod(): void
    {
        $port = new ReflectionClass(NumberSequenceAllocator::class);
        $methods = $port->getMethods();

        $this->assertTrue($port->isInterface(), 'The port must be a contract, not a class.');
        $this->assertSame(1, count($methods), 'The port does exactly one thing.');
        $this->assertSame([], $port->getInterfaceNames(), 'The port extends no other interface.');
        $this->assertSame([], $port->getConstants(), 'The port declares no constant.');

        $method = $methods[0];
        $return = $method->getReturnType();
        $this->assertSame('allocate', $method->getName(), 'The one thing is allocation.');
        $this->assertFalse($method->isStatic(), 'Allocation is asked of an adapter bound to a store.');
        $this->assertSame('int', $return instanceof ReflectionNamedType ? $return->getName() : null, 'int');
        $this->assertSame(
            [
                'siteIdentifier' => 'string',
                'definitionId' => 'string',
                'fieldHandle' => 'string',
                'scopeKey' => 'string',
                'periodKey' => 'string',
                'now' => DateTimeImmutable::class,
            ],
            $this->signature($method->getParameters()),
            'Five string coordinates and the instant, in the App\'s original order and with its names.',
        );
        foreach ($method->getParameters() as $parameter) {
            $this->assertFalse($parameter->isOptional(), $parameter->getName() . ' is required.');
            $this->assertFalse($parameter->allowsNull(), $parameter->getName() . ' is never null.');
        }
    }

    /**
     * The port declares the package-owned refusal and states the gapless-on-commit promise exactly.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testThePortDeclaresThePackageRefusalAndTheGaplessPromise(): void
    {
        $port = new ReflectionClass(NumberSequenceAllocator::class);
        $interface = (string) $port->getDocComment();
        $method = (string) $port->getMethod('allocate')->getDocComment();

        $this->assertStringContains('@throws  NumberSequenceUnavailable', $method, 'The one refusal is declared.');
        $this->assertStringContains('replayed rather than guess', $method, 'Replay, never guess.');
        $this->assertStringContains('contiguous from one', $interface, 'The contiguity promise.');
        $this->assertStringContains('consumes', $interface, 'A rolled-back command consumes nothing.');
        $this->assertStringContains('allocates nothing', $interface, 'A replayed command allocates nothing.');
        $this->assertStringContains('not* re-used', $interface, 'Numbers are never re-used.');
        $this->assertStringContains('one at a time', $interface, 'Contention is the price of contiguity.');
        $this->assertStringContains('storage-neutral', $interface, 'The port ships no implementation.');
        foreach (['Doctrine', 'FOR UPDATE', 'business_number_sequences'] as $storageDetail) {
            $this->assertStringExcludes($storageDetail, $interface . $method, 'No storage detail leaks.');
        }
    }

    /**
     * An in-memory reference implementation keeps the documented promises through the port alone.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testAnInMemoryReferenceKeepsTheDocumentedPromises(): void
    {
        $allocator = $this->reference();
        $at = new DateTimeImmutable('2026-03-01T00:00:00+00:00');

        $this->assertSame(1, $allocator->allocate('site', 'invoice', 'number', '-', '2026', $at), 'Runs start at one.');
        $this->assertSame(2, $allocator->allocate('site', 'invoice', 'number', '-', '2026', $at), 'Then advance by one.');
        $this->assertSame(1, $allocator->allocate('other', 'invoice', 'number', '-', '2026', $at), 'Another site.');
        $this->assertSame(1, $allocator->allocate('site', 'quotation', 'number', '-', '2026', $at), 'Another type.');
        $this->assertSame(1, $allocator->allocate('site', 'invoice', 'reference', '-', '2026', $at), 'Another field.');
        $this->assertSame(1, $allocator->allocate('site', 'invoice', 'number', 'north', '2026', $at), 'Another scope.');
        $this->assertSame(1, $allocator->allocate('site', 'invoice', 'number', '-', '2027', $at), 'Another period.');
        $this->assertSame(3, $allocator->allocate('site', 'invoice', 'number', '-', '2026', $at), 'Untouched by them.');
    }

    /**
     * A refusal reserves nothing: the run continues exactly where it stood once the counter is free again.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testARefusalReservesNothing(): void
    {
        $allocator = $this->reference();
        $at = new DateTimeImmutable('2026-03-01T00:00:00+00:00');
        $allocator->allocate('site', 'invoice', 'number', '-', '', $at);
        $allocator->hold('site|invoice|number|-|');

        $refusal = $this->assertThrows(
            static fn (): int => $allocator->allocate('site', 'invoice', 'number', '-', '', $at),
            NumberSequenceUnavailable::class,
            'A held counter answers with the one refusal.',
        );
        $this->assertSame(null, $refusal->getPrevious(), 'Detected directly, nothing chained.');
        $allocator->release('site|invoice|number|-|');
        $this->assertSame(2, $allocator->allocate('site', 'invoice', 'number', '-', '', $at), 'Nothing was consumed.');
    }

    /**
     * The port composes with the format: coordinates in, reserved value out, rendered number for the person.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testThePortComposesWithTheFormat(): void
    {
        $allocator = $this->reference();
        $format = NumberSequenceFormat::fromConfiguration(['reset' => 'yearly', 'prefix' => 'INV-', 'padding' => 6]);
        $at = new DateTimeImmutable('2026-05-05T10:00:00+00:00');
        $counter = $format->counter(null, $at);
        $rendered = [];
        for ($issued = 0; $issued < 3; $issued++) {
            $value = $allocator->allocate('site', 'invoice', 'invoice_number', $counter['scope'], $counter['period'], $at);
            $rendered[] = $format->render($value, $counter['period']);
        }

        $this->assertSame(['INV-2026-000001', 'INV-2026-000002', 'INV-2026-000003'], $rendered, 'Contiguous.');
    }

    /**
     * Map reflected parameters to their declared types, by name and in signature order.
     *
     * @param   list<\ReflectionParameter>  $parameters  Reflected parameters.
     *
     * @return  array<string, string|null>  Parameter names to type names.
     *
     * @since   0.1.0
     */
    private function signature(array $parameters): array
    {
        $signature = [];
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            $signature[$parameter->getName()] = $type instanceof ReflectionNamedType ? $type->getName() : null;
        }

        return $signature;
    }

    /**
     * An in-memory allocator that keeps the documented promises for one process; test support only.
     *
     * @return  NumberSequenceAllocator&object{hold: callable(string): void, release: callable(string): void}
     *
     * @since   0.1.0
     */
    private function reference(): NumberSequenceAllocator
    {
        return new class implements NumberSequenceAllocator {
            /**
             * Last value handed out per counter, keyed by the joined coordinates.
             *
             * @var    array<string, int>
             * @since  0.1.0
             */
            private array $counters = [];

            /**
             * Counters currently held by another allocator, keyed by the joined coordinates.
             *
             * @var    array<string, true>
             * @since  0.1.0
             */
            private array $held = [];

            /**
             * Mark a counter as held elsewhere so the next allocation refuses.
             *
             * @param   string  $key  Joined coordinates.
             *
             * @return  void
             *
             * @since   0.1.0
             */
            public function hold(string $key): void
            {
                $this->held[$key] = true;
            }

            /**
             * Release a held counter.
             *
             * @param   string  $key  Joined coordinates.
             *
             * @return  void
             *
             * @since   0.1.0
             */
            public function release(string $key): void
            {
                unset($this->held[$key]);
            }

            /**
             * Reserve the next value, refusing while the counter is held.
             *
             * @param   string             $siteIdentifier  Site coordinate.
             * @param   string             $definitionId    Definition coordinate.
             * @param   string             $fieldHandle     Field coordinate.
             * @param   string             $scopeKey        Scope coordinate.
             * @param   string             $periodKey       Period coordinate.
             * @param   DateTimeImmutable  $now             Ignored by the reference.
             *
             * @return  int  The reserved value.
             *
             * @throws  NumberSequenceUnavailable  While the counter is held.
             *
             * @since   0.1.0
             */
            public function allocate(
                string $siteIdentifier,
                string $definitionId,
                string $fieldHandle,
                string $scopeKey,
                string $periodKey,
                DateTimeImmutable $now,
            ): int {
                $key = implode('|', [$siteIdentifier, $definitionId, $fieldHandle, $scopeKey, $periodKey]);
                if (isset($this->held[$key])) {
                    throw new NumberSequenceUnavailable();
                }
                $this->counters[$key] = ($this->counters[$key] ?? 0) + 1;

                return $this->counters[$key];
            }
        };
    }
}
