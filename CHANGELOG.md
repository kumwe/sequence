# Changelog

Delivered package changes, newest first. A change is recorded here only after its stated proof passes on a
clean clone. The newest `## X.Y.Z` heading is the release record: a merge to `main` that carries it is the
release, published by the `Release on record` workflow ([`docs/releasing.md`](docs/releasing.md)).

## Unreleased

### Changed

- Add live package/CI/PHP/license badges and install the current 0.2.1 release explicitly.
- Replace completed handover prose with current Core and production release contracts.
- Keep archive/documentation gates aligned with the release record and retain all runtime semantics.

## 0.2.1

- **Consistent release automation.** Resolve release identity from the tested push commit after a rebase,
  verify release prerequisites before publication, and apply the same release checks in pull requests
  and after merge. Existing published releases are preserved; this record prepares a successor release.
- **Current consumer metadata.** Synchronize the three public manifests and migration handoff with this
  release candidate. Runtime behavior and the public API are unchanged.

## 0.2.0

- **Disjoint sequence scope keys.** Reject the reserved site marker `-` as an organization identifier.
  Previously it could produce the same counter key as a site-wide sequence, contrary to the published
  collision-free grammar. Other identifiers remain verbatim. This is an intentional refusal change and
  uses a pre-1.0 minor release; hosts must reject or migrate that reserved identifier before adoption.
- **Prove the archive as a dependency.** Install the actual built ZIP into a new no-dev Composer consumer,
  verify its installed files and authoritative classmap, and run the shipped example and smoke through
  the consumer autoloader. No path repository, source fallback or development toolchain participates.
- **Consistent release verification.** Use one tested parser for pushed and tagged changelogs, including
  Unreleased sections. Reject malformed brackets and leading-zero versions. Include security audit and
  twelve parser regressions in the single `composer check` entry point.
- **Extraction audit.** Reconciled the five extracted types with App baseline
  `960ce8ec00cf724a7cae03e5ba09c4852c9ab54e`; the reserved-marker refusal is the only production change.
  Database allocation, concurrency, authority and recovery remain App responsibilities.

## 0.1.0

- **The number format, reset and scope values.** Extracted from Kumwe App as a drop-in replacement:
  `Kumwe\Sequence\Value\NumberSequenceFormat` (a numbered field's declaration read into a validated value
  with a closed grammar — scope, reset, prefix, padding, timezone — the counter coordinates it composes, and
  deterministic rendering of an allocated value), `Kumwe\Sequence\Value\NumberSequenceReset` (`never`,
  `yearly`, `monthly`, `fiscal-period`, with the period key of an instant judged in the declared zone) and
  `Kumwe\Sequence\Value\NumberSequenceScope` (`site`, `organization`, with the closed scope-key grammar).
  Every bound, grammar, default, refusal and message is the App's, unchanged; the values refuse with
  `InvalidArgumentException` exactly as before.
- **The allocator port.** `Kumwe\Sequence\Contract\NumberSequenceAllocator`, the App's
  `BusinessNumberSequenceAllocator` with its signature, parameter names and gapless-on-commit promise
  preserved, redesigned around one package-owned refusal: the port now declares
  `Kumwe\Sequence\Exception\NumberSequenceUnavailable` instead of an App record exception, so a host's retry
  policy catches one canonical type and no driver classification leaks through the port. The package ships
  no implementation, no transaction, no lock and no retry policy; the host binds its own adapter.
- **The contract is executable evidence.** The suite pins the declaration grammar and bounds, the reset
  keys at their calendar boundaries in the declared zone and independent of the process zone, the scope-key
  grammar, enum equality and serialization, the port's shape and documented promises through an in-memory
  reference, the refusal's identity, the shipped example, and the three Version 2 manifests against the
  source tree, the changelog and the API documentation; enum cases are recorded surface.
- **Founding.** Charter, README, architecture, integration, releasing and security documents, the complete
  public API document, `resources/public-api/v1.json`, `resources/capabilities/v1.json` and
  `resources/service-map/v1.json` in the Kumwe App governance schemas, and the check lane: strict Composer
  metadata, lint and column limits, member documentation, the architecture boundary, manifests, the
  Composer-autoload proof, the runnable example, PSR-12, PHPStan level max with strict and deprecation
  rules, the dependency-free suite, and the built-archive clean-consumer gate. Continuous integration runs
  the lane on PHP 8.5 with pinned actions and least-authority permissions; release-on-record publishes the
  recorded version after a human merge and never accepts a hand-made tag.
