<?php

declare(strict_types=1);

namespace Kumwe\Sequence\Value;

use InvalidArgumentException;

/**
 * The tenancy boundary an allocated document number is unique and contiguous within.
 *
 * A number sequence is never installation-wide: it is always at least per site, because two sites sharing
 * one store are two businesses and each needs its own invoice run. `Organization` narrows it further for a
 * definition whose records are scoped to an organization branch, so each branch keeps its own contiguous
 * run instead of interleaving with its siblings. The key is resolved from the record's own resolved scope
 * rather than from caller input, which is what keeps one tenant from allocating into another's counter.
 *
 * The key this enum composes is one coordinate of five: a counter is the tuple of site, definition, field
 * handle, scope key and period key. The definition with its field handle is the document type, so every
 * allocated-number field of every entity type draws from counters exclusively its own, and the site is the
 * legal entity — the ownership boundary a business's books never share. Those coordinates come from the
 * declaring field and the record's resolved scope; what is declared here is only how far the run subdivides
 * within them. When that record scope carries no site dimension, the legal-entity coordinate is the
 * definition's own immutable catalog site.
 *
 * @since  0.1.0
 */
enum NumberSequenceScope: string
{
    /**
     * One counter per site; every record on the site shares the run.
     *
     * @since  0.1.0
     */
    case Site = 'site';

    /**
     * One counter per organization branch within the site.
     *
     * @since  0.1.0
     */
    case Organization = 'organization';

    /**
     * Resolve the counter key for the organization a record was scoped to.
     *
     * The key grammar is fixed and closed: `-` names the site-wide counter, so an organization identifier
     * can never collide with it, and a per-branch counter is keyed by the organization identifier exactly
     * as the host resolved it. The identifier's own grammar is the host's; this package only refuses an
     * absent or empty one and the reserved site marker, because those cannot identify a separate branch run.
     *
     * @param   ?string  $organizationIdentifier  Organization the record belongs to, or null when its
     *          definition's scope mode carries no organization dimension.
     *
     * @return  string  `-` for a site-wide counter, and the organization identifier for a per-branch one.
     *
     * @throws  InvalidArgumentException  When a per-organization sequence is declared on a definition whose
     *          scope mode carries no organization dimension, or whose organization uses the reserved site marker.
     *
     * @since   0.1.0
     */
    public function key(?string $organizationIdentifier): string
    {
        if ($this === self::Site) {
            return '-';
        }
        if ($organizationIdentifier === null || $organizationIdentifier === '') {
            throw new InvalidArgumentException(
                'A per-organization number sequence requires a record scope carrying an organization.',
            );
        }
        if ($organizationIdentifier === '-') {
            throw new InvalidArgumentException(
                'A per-organization number sequence cannot use the reserved site-wide counter key.',
            );
        }

        return $organizationIdentifier;
    }
}
