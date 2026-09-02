<?php

declare(strict_types=1);

namespace Kumwe\Sequence\Exception;

use RuntimeException;
use Throwable;

/**
 * The one refusal an allocation answers with: the counter cannot be reserved now, so replay, never guess.
 *
 * An allocator that finds its counter held by another transaction, created first by a racing first use,
 * timed out waiting for it, deadlocked, or lost the compare-and-set it asserts on a store without row locks
 * has reserved nothing — and every one of those is the same answer to the caller: nothing was consumed, the
 * run is intact, replay the command. One canonical type carries that answer so a host's retry policy can
 * catch exactly it, and so an adapter cannot leak a driver's own classification through the port. The
 * failure the adapter translated stays reachable as the chained previous exception, for the log.
 *
 * It is never raised for a malformed declaration or an unrenderable value: those are argument errors the
 * value types refuse with `InvalidArgumentException` before any counter is touched.
 *
 * @since  0.1.0
 */
final class NumberSequenceUnavailable extends RuntimeException
{
    /**
     * Build the refusal, chaining the failure it stands in for when there is one.
     *
     * @param  ?Throwable  $previous  Driver or infrastructure failure being translated, kept for the log;
     *         null when the condition was detected directly rather than caught.
     *
     * @since  0.1.0
     */
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            'The number sequence counter is temporarily unavailable; replay the allocation.',
            0,
            $previous,
        );
    }
}
