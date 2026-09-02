# Kumwe Sequence

**Portable numbering format, reset and scope values, and the allocator port. The host reserves the numbers;
this package says what they mean.**

Kumwe Sequence defines document numbering for the [Kumwe](https://github.com/kumwe) family: the declaration
an allocated-number field carries (`NumberSequenceFormat`, with its `NumberSequenceReset` and
`NumberSequenceScope` vocabularies), the counter coordinates that declaration composes, the rendering of a
reserved value into the number a person reads, and `NumberSequenceAllocator`, the storage-neutral port a
host implements to reserve the next value inside its own transaction. The package ships **no allocator
implementation of any kind**: the counter store, its locks, its deadlock retry and its database evidence all
stay in the host that owns the store.

## Responsibility and non-goals

This package owns:

- the declaration grammar and its bounds — scope, reset, prefix, padding and timezone, what each admits,
  what each refuses and what it defaults to;
- the counter coordinates a declaration composes for a record scope and an instant, and the period keys at
  their calendar boundaries in the declared zone;
- deterministic rendering of a reserved value, and the refusal of a value the format cannot hold;
- the allocator port with its gapless-on-commit promise stated exactly, and the one refusal an allocation
  answers with;
- their complete documentation, the three machine-readable manifests and the tests that pin all of it.

It does not own, and will refuse:

- any allocator implementation, counter store, transaction, lock, fence, unique-index arbitration or retry;
- business numbering policy — which documents are numbered, what an invoice or stock number is, repair;
- a fiscal calendar: a fiscal-period key is a posting period the host declared;
- site and organization authority: the record scope a counter is keyed by is the host's to resolve;
- container registration: there is no `ConfigProvider`, factory or alias here (see below);
- real-database evidence, which the host proves on the engines it supports.

## Installation and supported platforms

```bash
composer require kumwe/sequence
```

PHP `^8.5` and nothing else: the runtime requirement is `php` alone, with no extension and no Composer
dependency. While the package is pre-1.0 a consumer pins an exact version, as
[`docs/releasing.md`](docs/releasing.md) explains.

## Canonical namespace

`Kumwe\Sequence\` is the one canonical root, autoloaded PSR-4 from `src/`. Values live under
`Kumwe\Sequence\Value\`, the port under `Kumwe\Sequence\Contract\`, the refusal under
`Kumwe\Sequence\Exception\`. There is no alias, historical namespace or compatibility root.

## Five-minute example

```php
<?php

declare(strict_types=1);

use DateTimeImmutable;
use Kumwe\Sequence\Contract\NumberSequenceAllocator;
use Kumwe\Sequence\Exception\NumberSequenceUnavailable;
use Kumwe\Sequence\Value\NumberSequenceFormat;

final readonly class IssueInvoice
{
    public function __construct(private NumberSequenceAllocator $numbers)
    {
    }

    public function issue(DateTimeImmutable $now, ?string $organization): string
    {
        $format = NumberSequenceFormat::fromConfiguration([
            'reset' => 'yearly',
            'prefix' => 'INV-',
            'padding' => 6,
            'timezone' => 'Africa/Windhoek',
        ]);
        $counter = $format->counter($organization, $now);
        try {
            $value = $this->numbers->allocate(
                'default',                 // site: the legal entity
                'invoice',                 // definition: the document type
                'invoice_number',          // field handle
                $counter['scope'],         // '-' for a site-wide run
                $counter['period'],        // '2026', judged in Windhoek
                $now,
            );
        } catch (NumberSequenceUnavailable $refusal) {
            throw $refusal;                // nothing was reserved: replay the whole command, never guess
        }

        return $format->render($value, $counter['period']);   // INV-2026-000001
    }
}
```

The service never sees a store. `counter()` names exactly one counter from the declaration, the record's
organization and the instant; `allocate()` reserves its next value inside the transaction the caller already
holds; `render()` turns that value into the printed number. A runnable version against an in-memory
reference allocator is [`examples/allocate-and-render.php`](examples/allocate-and-render.php).

## Dependency injection: no provider, by design

This is a value and port package. It exports three directly constructible values, one interface and one
exception, and no runtime service with collaborators, so it ships no Laminas/Mezzio `ConfigProvider`, no
factory and no alias: an empty provider would be ceremony, not integration. Values are built from a
declaration with `NumberSequenceFormat::fromConfiguration()`; enums are used as cases; the refusal is thrown
by an adapter. The host constructs its allocator adapter and binds it to the port identifier itself, as
Kumwe App does in its composition root:

```php
$container->share(NumberSequenceAllocator::class, static fn (Container $c): NumberSequenceAllocator =>
    new DoctrineBusinessNumberSequenceAllocator($c->get(Connection::class), $c->get(TableNames::class)));
```

The adapter is **request- or operation-supplied** in the package standard's vocabulary: it is bound to one
connection whose open transaction every allocation joins, and it must never be shared across threads or
processes. [`resources/service-map/v1.json`](resources/service-map/v1.json) records the absence and its
reason; [`docs/integration.md`](docs/integration.md) walks through the binding and what the host must prove.

## Public surface

| Symbol | Kind | Capability | Lifetime |
| --- | --- | --- | --- |
| `Kumwe\Sequence\Value\NumberSequenceFormat` | final readonly class | `sequence.format` | value, built per declaration |
| `Kumwe\Sequence\Value\NumberSequenceReset` | string-backed enum | `sequence.reset` | case |
| `Kumwe\Sequence\Value\NumberSequenceScope` | string-backed enum | `sequence.scope` | case |
| `Kumwe\Sequence\Contract\NumberSequenceAllocator` | interface | `sequence.allocation` | host-supplied per connection |
| `Kumwe\Sequence\Exception\NumberSequenceUnavailable` | final exception | `sequence.allocation` | thrown per refusal |

No factories, no aliases, no configuration keys, no defaults: there is nothing to configure. Every symbol
and member is documented in [`docs/public-api.md`](docs/public-api.md) and pinned in
[`resources/public-api/v1.json`](resources/public-api/v1.json), enum cases included; `composer manifests`
refuses drift. Capabilities are recorded in [`resources/capabilities/v1.json`](resources/capabilities/v1.json).

## Guarantees

- **Errors and refusals.** A declaration that is not in the grammar, a per-organization counter with no
  organization, a fiscal reset asked for a calendar key, a non-positive value, or a value the column cannot
  hold is refused with `InvalidArgumentException` — an argument error, exactly as the App refused it. The
  port declares one refusal, `NumberSequenceUnavailable`: the counter cannot be reserved now, nothing was
  consumed, replay. An adapter translates its driver's contention into that type and keeps the original
  reachable as the previous exception; it never lets a driver class escape the port.
- **Mutability.** `NumberSequenceFormat` is `final readonly`; the enums are cases; the exception carries
  nothing but its fixed message and the chained cause. Nothing in the package holds state between calls.
- **Determinism.** `render()` is a pure function of its arguments. `counter()` reads only the instant it is
  handed and the declared zone — never the process default zone, never a clock. The keys are the same on
  every replica for the same instant.
- **Precision and canonicalization.** Padding is a minimum width, never a truncation: a value wider than
  its padding renders in full until the column limit refuses it. Prefix and period key render verbatim.
- **Bounds.** Prefix up to 16 of `A-Z 0-9 - /`; padding 1 to 12 digits; the widest rendered number is 36
  characters, and a host sizes its column to `NumberSequenceFormat::MAXIMUM_LENGTH`.
- **Transaction expectations.** `allocate()` runs inside the transaction the caller already opened and must
  not open its own; the reservation commits or rolls back with the command. Within one counter, committed
  records are numbered contiguously from one; a rolled-back command consumes nothing; a replayed idempotent
  command allocates nothing; numbers are never re-used, so a hard delete leaves a hole nothing fills.
- **Concurrency and process safety.** The values are immutable and safe to share. An adapter holds the
  counter exclusively until its transaction ends, so concurrent commands against one counter serialize —
  the price of contiguity, and the reason scope and reset are chosen deliberately. An adapter is bound to
  one connection and is not shared across threads or processes.

## Extension and replacement points

`NumberSequenceAllocator` is the extension point: a host implements it once per store technology. The three
values and the exception are final; a new scope or reset case is a reviewed change to this package with a
new version, never a subclass or a host-side copy. A consumer that needs an in-memory allocator for its own
tests writes one against the port, as the example and the suite do.

## Migration from Kumwe App

`Kumwe\App\BusinessDefinition\Domain\NumberSequenceFormat`, `NumberSequenceReset` and `NumberSequenceScope`
become `Kumwe\Sequence\Value\NumberSequenceFormat`, `NumberSequenceReset` and `NumberSequenceScope`,
behaviour unchanged. `Kumwe\App\BusinessRecord\Application\BusinessNumberSequenceAllocator` becomes
`Kumwe\Sequence\Contract\NumberSequenceAllocator` with the same signature and parameter names; its
`@throws` moves from the App's `BusinessRecordTemporarilyUnavailable` to the package's
`NumberSequenceUnavailable`, so the App's adapter raises the package refusal and the App's record service
translates it into its own retryable exception at the port boundary. The Doctrine adapter, the kernel
binding, the definition validator, the posting-period calendar and every database test stay in the App.
The complete adoption record, with file-level Phase 2 instructions, is `MIGRATION-HANDOFF.md` in this
repository.

## Testing and clean-consumer commands

```bash
composer install
composer check                          # the complete lane, ending in the built-archive clean-consumer gate
php tests/run.php                       # the dependency-free suite alone; no composer install needed
php examples/allocate-and-render.php    # the shipped example; no composer install needed
```

The lane is described in [`docs/architecture.md`](docs/architecture.md); the archive and clean-consumer
proof in [`docs/releasing.md`](docs/releasing.md).

## Release, compatibility, security and license

Releases are on the record: the newest `## X.Y.Z` heading in [`CHANGELOG.md`](CHANGELOG.md) is the release
a merge to `main` publishes, as [`docs/releasing.md`](docs/releasing.md) describes. Compatibility follows
semantic versioning with exact consumer pins while pre-1.0. Security policy and scope are in
[`docs/security.md`](docs/security.md). Licensed under the [Apache License, Version 2.0](LICENSE).
