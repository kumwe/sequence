<?php

declare(strict_types=1);

namespace Kumwe\Sequence\Contract;

use DateTimeImmutable;
use Kumwe\Sequence\Exception\NumberSequenceUnavailable;

/**
 * Reserves the next value of one number counter, inside the caller's own transaction.
 *
 * This is the primitive behind an allocated-number field, and its whole reason to exist is that a document
 * number is not a deduplicated string: an invoice run has to be *contiguous*, which a unique index can only
 * ever refuse to break, never produce. An implementation therefore serializes concurrent allocators on the
 * counter and advances it by exactly one, and it must do so in the transaction the record command already
 * opened — that is what makes a rolled-back create give its number back instead of burning it.
 *
 * The guarantee an implementation owes, stated exactly:
 *
 * - Within one counter — site, definition, field handle, scope key and period key together — the values
 *   handed to *committed* records are contiguous from one, with no duplicates and no gaps.
 * - A command that rolls back for any reason, including a later failure in the same transaction, consumes
 *   nothing.
 * - A replayed idempotent command allocates nothing; it returns the number the original command stored.
 * - Numbers are *not* re-used, and a gap can still appear afterwards if a numbered row is hard-deleted or
 *   an operator edits the counter. Gaplessness is a property of allocation, not of the row surviving.
 * - Allocation holds the counter exclusively until the enclosing transaction ends, so concurrent creates
 *   against one counter run one at a time. That is the price of contiguity, and it is the reason the scope
 *   and reset period on an allocated-number field are worth choosing deliberately.
 * - When the counter cannot be reserved now — another allocator holds it, created it first, or won the
 *   compare-and-set the implementation asserts — the implementation reserves nothing and raises
 *   `NumberSequenceUnavailable`, so the caller replays the whole command rather than guess at a value.
 *
 * The port is storage-neutral: this package ships no implementation, no transaction, no lock and no retry
 * policy. The host that owns the store implements it once, binds its adapter to this interface, and proves
 * the guarantee on every engine it supports.
 *
 * @since  0.1.0
 */
interface NumberSequenceAllocator
{
    /**
     * Reserve the next value of the counter these coordinates name.
     *
     * @param   string             $siteIdentifier  Site the numbered record belongs to, or the definition's
     *          own immutable catalog site when the record scope itself carries no site dimension.
     * @param   string             $definitionId    Identifier of the definition declaring the numbered field;
     *          with the field handle it is the document type the counter belongs to.
     * @param   string             $fieldHandle     Handle of the allocated-number field being filled.
     * @param   string             $scopeKey        Tenancy key from `NumberSequenceFormat::counter()`.
     * @param   string             $periodKey       Period key from that same call; empty for a lifetime run.
     * @param   DateTimeImmutable  $now             Instant the allocation is being made at.
     *
     * @return  int  The reserved value, exactly one higher than the last committed allocation; always one or
     *          more, and never higher than the widest number the field's format can render.
     *
     * @throws  NumberSequenceUnavailable  When another allocator holds or first created this counter and
     *          this attempt must be replayed rather than guess at a value; nothing was reserved.
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
    ): int;
}
