# The Sequence charter

**Kumwe Sequence** is the portable document-numbering package of the Kumwe family: the number format, reset
period and tenancy scope an allocated-number field declares, the storage-neutral allocator port a host
implements to reserve the next value of one counter, and the one refusal an allocation can answer with. It
ships no allocator implementation, no storage and no numbering policy. The public contract fixes the
declaration grammar, counter coordinates, rendering and refusal behavior. Core owns database allocation,
authority, transactions, retries and recovery.

This charter is normative for the repository. A change that contradicts it is a defect, whatever tests it
passes.

## What Sequence is

1. **The grammar, stated once.** What a numbered field may declare — which scopes, which resets, which
   prefix characters, how much padding, which zone — and what the rendered number therefore looks like, is
   decided here and nowhere else. Two hosts, or one host and its extensions, that render the same counter
   value under the same declaration produce the same text, because neither owns the step that produces it.
2. **A port, not an engine.** The package says what an allocation promises — contiguous from one within one
   counter on committed records, nothing consumed by a rolled-back or replayed command, nothing reserved by
   a refusal, one allocator at a time per counter — and says nothing about how a store keeps that promise.
3. **Proven by construction and refusal.** Every declaration demonstrates what it admits and what it
   refuses; every period key is pinned at its calendar boundary in the declared zone; the port's shape and
   promises are asserted by the suite and held by an in-memory reference. A capability the suite does not
   prove is not claimed.

## What Sequence contains

- **`Kumwe\Sequence\Value\NumberSequenceFormat`** — a numbered field's declaration read into a validated
  value: scope, reset, prefix, padding and timezone with their closed grammar and fixed bounds; the two
  counter coordinates it composes for a record scope and an instant; and the deterministic rendering of an
  allocated value into the number a person reads.
- **`Kumwe\Sequence\Value\NumberSequenceReset`** — `never`, `yearly`, `monthly` and `fiscal-period`, with
  the period key an instant maps to in the declared zone.
- **`Kumwe\Sequence\Value\NumberSequenceScope`** — `site` and `organization`, with the closed scope-key
  grammar that keeps one tenant out of another's counter.
- **`Kumwe\Sequence\Contract\NumberSequenceAllocator`** — the port a host implements: reserve the next value
  of the counter five coordinates name, inside the caller's own transaction.
- **`Kumwe\Sequence\Exception\NumberSequenceUnavailable`** — the one refusal an allocation answers with:
  the counter cannot be reserved now, nothing was consumed, replay rather than guess.

## What Sequence must never contain

1. **No allocator implementation of any kind.** A row lock, a compare-and-set, a database sequence, a
   unique-index arbitration, a deadlock retry: every one of them is an implementation of the port defined
   here, living in the host that owns the store, never in this package.
2. **No business numbering policy.** Which documents are numbered, what an invoice, quotation or stock
   number is, whether a hole may be repaired and by whom, belong to the host and its extensions.
3. **No fiscal calendar.** A fiscal-period key is a posting period the host declared; this package renders
   the key it is handed and refuses to invent one.
4. **No authority.** The site and organization a counter is keyed by are resolved and enforced by the host;
   this package composes keys from what it is told and never decides who may allocate.
5. **No container wiring.** There is no `ConfigProvider`, no factory and no alias here; the host binds its
   adapter to the port identifier in its own container. An empty provider would be ceremony.
6. **No runtime Composer dependency.** The package runs on PHP alone, so a host adopts a contract, not a
   dependency tree.
7. **No production fallback.** The in-memory allocators in the example and the suite are demonstration and
   test support. Nothing in this package may be bound as an allocator in production, and no host may keep a
   copy of these types under another name.

## The non-negotiable rule

> **Within one counter, the numbers on committed records run contiguously from one; a command that fails
> consumes nothing; a refusal reserves nothing.**

An adapter that hands out a value outside the caller's transaction, re-uses a number, answers a held counter
with a guess, or lets a driver's own failure class escape the port has not implemented this contract,
whatever interface it declares.

## Canonical ownership and compatibility

- Every public type lives under `Kumwe\Sequence\`, its one canonical runtime namespace. Core imports
  these types and supplies its allocator through the port; no historical namespace alias is supplied.
- The port uses `NumberSequenceUnavailable`. Core translates it to its own retryable record exception at
  the application boundary. The reserved organization key `-` is refused to keep counter scopes disjoint.
- [The release record](docs/release-record.md) preserves historical source and consumer evidence. Review
  current consumers before replacing implementations. Retain database, contention, validator and
  composition tests; remove implementation-only duplicates with the implementation they exercised.
- The public manifest pins every exported member and enum case. Semantic changes require a reviewed
  successor version and compatibility proof; historical source correspondence does not override the
  current released contract.

## The boundary in one line

**This package says what a number means and what an allocation promises. The host says how its store
keeps the promise.**

## Relationships

- **With Kumwe App** ([`kumwe/app`](https://github.com/kumwe/app)): the App is Sequence's first consumer,
  never its owner. It pins an exact version, imports the canonical names directly, owns its Doctrine
  allocator adapter and the container binding, owns the definition validator that refuses an open or too
  narrow numbered field, owns the posting-period calendar a fiscal key comes from, and proves its adapter on
  MariaDB, MySQL and PostgreSQL under contention.
- **With dependent packages**: a package that needs numbering vocabulary depends on this one and types
  against the values and the port; it never ships an adapter of its own.

## Governance

Delivered behaviour is recorded in [`CHANGELOG.md`](CHANGELOG.md); a claim states only what the check lane
proves on a clean clone. The check lane is `composer check`: strict Composer metadata, syntax and column
limits, member documentation, the architecture boundary, the three manifests against source and
documentation, the Composer-autoload proof, the runnable example, PSR-12, PHPStan level max with strict and
deprecation rules, the dependency-free suite, and the built-archive clean-consumer gate. Every commit passes
it. The engineering rules live in [`docs/architecture.md`](docs/architecture.md).
