# Architecture and engineering standard

This document says how the package is built and what "good" means in this repository, so quality is a
stated, checkable expectation. The [charter](../CHARTER.md) says what Sequence is; this says how it is
kept that way. It descends from the Kumwe App coding standard and the Kumwe Version 2 package standard,
adapted to a package whose whole subject is one vocabulary and one port.

## Layers

Sequence is three layers with one dependency direction. A layer may use the layers listed for it and never
the others; `composer architecture` reads the token stream of every source file and refuses a crossing, a
driver, a host, a container or a framework name.

| Layer | Namespace | Owns | May use |
| --- | --- | --- | --- |
| Value | `Kumwe\Sequence\Value` | the declaration, the reset and scope vocabularies, keys and rendering | Value, PHP |
| Contract | `Kumwe\Sequence\Contract` | the allocator port and its documented promises | Contract, Exception, PHP |
| Exception | `Kumwe\Sequence\Exception` | the one refusal the port declares | Exception, PHP |

Rules that keep the boundary honest:

1. **The released behaviour is the API.** Public constants, properties, methods, parameter names, types,
   messages and refusals match the documented release. The allocator port uses the package refusal;
   organization key `-` is rejected to keep scopes disjoint. Compatibility decisions remain in
   `CHANGELOG.md` and [release evidence](release-record.md). Behavior changes require a reviewed version.
2. **The values refuse natively.** A declaration outside the grammar, a counter that cannot be named, a
   value that cannot be rendered, is an `InvalidArgumentException`: an argument error a host's validator
   turns into its own publication refusal. The package-owned exception is reserved for the port, where a
   host's retry policy needs one identity.
3. **A port carries no implementation.** No class in `Contract` has a body; no class in the package reads
   or writes a store, opens a transaction, or takes a lock. The only allocators in the repository are the
   in-memory references in the example and the suite, and neither ships in `src/`.
4. **Nothing is selected at runtime.** No `class_exists()`, `extension_loaded()`, `class_alias()` or
   fallback of any kind appears in `src/`; the architecture gate refuses them.
5. **Determinism.** The package reads no clock, no environment and no randomness. A period key is judged in
   the declared zone, never the process default; the suite changes the default zone and proves it.
6. **One owner, one name.** `Kumwe\Sequence\` is the only namespace. No historical App name survives
   in runtime source or public manifests, and the suite refuses one in the manifests and the source.

## Code

- `declare(strict_types=1)` in every file; `final` classes by default and `final readonly` wherever the
  instance carries no mutable state; native types on every parameter and return; typed class constants; one
  class-like declaration per file, named after the file, autoloaded PSR-4 from `Kumwe\Sequence\`.
- No runtime Composer dependency, ever (`php` alone), and no extension: a host adopts a contract, not a
  dependency tree.
- Every line of every tracked file — code, documentation, manifests, workflows — stays at or below 120
  columns; `composer lint` enforces it.
- No timestamp, commit hash or machine path is committed into a generated artifact; the same source
  produces identical manifests on every supported runtime.

## Documentation blocks

Every documentable member — class-like declaration, method, non-promoted property, class constant, enum
case — carries a documentation block, enforced by `composer docs`. The format is the Kumwe App standard:

1. A summary sentence stating what the member does, then optionally a paragraph on when to reach for it —
   the guarantee it makes and which collaborator owns the parts it does not.
2. Aligned, ordered tags: `@param` entries in signature order (promoted constructor properties included),
   then `@return` (except constructors), then `@throws` with the condition each entry is raised under, then
   the trailing group. Within a block, tag values start in one column: two spaces after the longest tag
   name in the block.
3. `@since` is always last and always present; every member of this package records `0.1.0`, the release
   that introduced it here, and it is never rewritten.
4. Types are precise — `array{scope: string, period: string}`, `array<string, scalar|list<scalar|null>|null>`
   — never a bare `array` in a documentation block, and an existing documented type is never widened or
   dropped.

## Testing

The suite exists to prove intended outcomes, and only that:

1. **A test asserts an observable contract.** The grammar and its bounds, a period key at its boundary, a
   scope key, a rendered number, the port's shape and promises, the refusal's identity, the manifests
   against the source, the example's output — never an implementation detail.
2. **Refusals are pinned as firmly as acceptances.** Every documented refusal is provoked and asserted, with
   its message where the message is part of the contract.
3. **The port is proven implementable.** An in-memory reference in the suite implements the port with no
   host type and is held to the documented promises: contiguity from one, independent counters per
   coordinate, a refusal that consumes nothing.
4. **Nothing frivolous.** A test that cannot fail for a reason a consumer would care about is not written.
5. **Dependency-free and deterministic.** `php tests/run.php` runs on a clean clone with no composer
   install, touches no network and no clock, and passes in any order. Discovery fails closed.

Test ownership across the boundary: this package owns the grammar, keys, rendering, port shape and
promises, refusal identity and manifests. A host owns everything that needs a store or an authority —
contention on a real counter across connections, first-use races, rollback returning a number, replay
allocating nothing, deadlock and lock-wait classification, the definition validator's closed-field and
column-width rules, the posting-period calendar, the composition that binds the adapter — and proves it on
the engines it supports.

## The check lane

After `composer install`, `composer check` is the whole standard, executable, in this order:

| Step | Command | Proves |
| --- | --- | --- |
| Composer metadata | `composer validate --strict` | the package coordinate is publishable |
| Lint | `php tools/lint.php` | every PHP file parses; every tracked line fits 120 columns |
| Documentation | `php tools/check-docblocks.php` | every member of `src/` and `examples/` is documented |
| Architecture | `php tools/verify-architecture.php` | the layer rules, no coupling, PHP-only runtime |
| Manifests | `php tools/verify-manifests.php` | the three manifests agree with source, changelog, docs |
| Autoload smoke | `php resources/toolchain/autoload-smoke.php` | Composer loads every exported symbol |
| Example | `php examples/allocate-and-render.php` | the shipped example runs |
| Style | `phpcs -q` | PSR-12 with a hard 120-column limit |
| Static analysis | `phpstan analyse` | level max with strict and deprecation rules, source and tooling |
| Suite | `php tests/run.php` | the dependency-free behavioural and manifest suite |
| Clean consumer | `php tools/verify-clean-consumer.php` | the built archive installs and runs no-dev |

Every commit passes it. CI runs it on every supported PHP version and then removes the development
packages, rebuilds an authoritative production autoloader, and runs the smoke and the example again. A
release re-proves the same lane on the merged commit before a tag exists.

## Versioning and drift

The public API manifest is the compatibility pin: `composer manifests` regenerates it from reflection and
refuses any difference, enum cases included. A reviewed change records the new surface with
`composer manifests:record` and a changelog entry; routine changes never rewrite the evidence to make the
gate green. A change a consumer must act on is a new major. Newer portable behaviour discovered in a
consumer is routed here as a successor release before that consumer adopts it; see
[the release contract](release-record.md).
