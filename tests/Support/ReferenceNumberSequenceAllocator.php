<?php

declare(strict_types=1);

namespace Kumwe\Sequence\Tests\Support;

use DateTimeImmutable;
use Kumwe\Sequence\Contract\NumberSequenceAllocator;
use Kumwe\Sequence\Exception\NumberSequenceUnavailable;

/**
 * An in-memory allocator that keeps the documented promises for one process; test support only.
 *
 * It is what a host adapter promises minus everything a store provides — durability, the transaction the
 * allocation joins, the lock that serializes concurrent allocators. A held counter answers with the one
 * refusal and reserves nothing, exactly as the port documents. It lives under the test tree so it can never
 * ship in the archive and never be bound in production.
 *
 * @since  0.1.0
 */
final class ReferenceNumberSequenceAllocator implements NumberSequenceAllocator
{
    /**
     * Last value handed out per counter, keyed by the five coordinates joined with `|`.
     *
     * @var    array<string, int>
     * @since  0.1.0
     */
    private array $counters = [];

    /**
     * Counters currently held by another allocator, keyed the same way.
     *
     * @var    array<string, true>
     * @since  0.1.0
     */
    private array $held = [];

    /**
     * Mark a counter as held elsewhere so the next allocation refuses.
     *
     * @param   string  $key  The five coordinates joined with `|`.
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
     * @param   string  $key  The five coordinates joined with `|`.
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
     * Reserve the next value of one counter, refusing while the counter is held.
     *
     * @param   string             $siteIdentifier  Site coordinate.
     * @param   string             $definitionId    Definition coordinate.
     * @param   string             $fieldHandle     Field coordinate.
     * @param   string             $scopeKey        Scope coordinate.
     * @param   string             $periodKey       Period coordinate.
     * @param   DateTimeImmutable  $now             Ignored by the reference; a store would stamp it.
     *
     * @return  int  The reserved value, contiguous from one per counter.
     *
     * @throws  NumberSequenceUnavailable  While the counter is held; nothing is consumed.
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
}
