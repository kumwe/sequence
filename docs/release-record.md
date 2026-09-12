---
schema: kumwe-package-release-record/v1
artifact_kind: framework_php
migration_id: KUMWE-MIG-2026-002
change_set: KUMWE-CS-2026-002
source:
  app:
    repository: https://github.com/kumwe/app
    baseline_commit: 960ce8ec00cf724a7cae03e5ba09c4852c9ab54e
    examined_paths:
    - src/BusinessDefinition/Domain/NumberSequenceFormat.php
    - src/BusinessDefinition/Domain/NumberSequenceReset.php
    - src/BusinessDefinition/Domain/NumberSequenceScope.php
    - src/BusinessRecord/Application/BusinessNumberSequenceAllocator.php
    - src/BusinessRecord/Application/Exception/BusinessRecordTemporarilyUnavailable.php
    - src/BusinessRecord/Application/BusinessRecordService.php
    - src/BusinessRecord/Application/RecordValueCodec.php
    - src/BusinessRecord/Application/RecordRuleValidator.php
    - src/BusinessRecord/Infrastructure/Persistence/DoctrineBusinessNumberSequenceAllocator.php
    - src/BusinessDefinition/Application/BusinessDefinitionValidator.php
    - src/BusinessSchema/Infrastructure/Schema/CanonicalDefinitionPhysicalSchemaCompiler.php
    - src/Infrastructure/Persistence/Migration/BusinessNumberSequenceMigration.php
    - src/Kernel/ContainerFactory.php
    - src
    - tests/Unit/BusinessDefinition/Domain/NumberSequenceFormatTest.php
    - tests/Unit/BusinessDefinition/Application/AllocatedNumberFieldRuleTest.php
    - tests/Unit/BusinessDefinition/Application/FiscalPeriodSequenceCoherenceTest.php
    - tests/Unit/BusinessDefinition/Application/NumberSequenceScopeCoherenceTest.php
    - tests/Integration/BusinessRecord/BusinessNumberSequenceContentionIntegrationTest.php
    - tests/Integration/BusinessRecord/BusinessNumberSequenceHotPathIntegrationTest.php
    - tests/Integration/BusinessRecord/BusinessNumberSequenceIdentityIntegrationTest.php
    - tests/Integration/BusinessRecord/BusinessRecordClientReferenceIntegrationTest.php
    - tests/Integration/BusinessRecord/BusinessRecordDeadlockIntegrationTest.php
    - tests/Integration/BusinessRecord/FiscalPeriodSequenceIntegrationTest.php
    - tests/Integration/Persistence/TransactionBoundaryEngineIntegrationTest.php
    - tests/Architecture/ClientAssertedInstantBoundaryTest.php
    - tests
    - config
    - bootstrap
    - examples
    - composer.json
    - composer.lock
    - docs/architecture/layers.json
    - docs/architecture/capability-index.md
    - docs/architecture/governance/core-growth-baseline.json
    - docs/architecture/governance/legacy-packages.json
    - docs/architecture/non-roadmap
    - docs/quality/baseline.json
    - vendor/kumwe/conversion
    - vendor/kumwe/extension-sdk
    - vendor/kumwe/producer
    old_namespace_roots:
    - Kumwe\App\BusinessDefinition\Domain\
    - Kumwe\App\BusinessRecord\Application\
    capability_index_sha256: 87ded886f35f74878ca9eb8db4c36e23d681c4a49891f76dfc3210f385a7ce39
  semantic_inputs: []
  examined_dependencies:
  - kumwe/conversion 0.1.2 (installed, legacy-unmanifested); decimal and conversion semantics, no numbering
  - kumwe/extension-sdk 0.2.4 (installed, legacy-unmanifested); extension contracts, no numbering or counter
  - kumwe/producer 0.2.0 (installed, legacy-unmanifested); Studio wire operations, no numbering or counter
  - no Kumwe dependency selected; the allowed ceiling is None and the runtime requirement is php ^8.5
    alone
target:
  repository: https://github.com/kumwe/sequence
  artifact_identity: kumwe/sequence (Composer library)
  canonical_namespace_or_abi: Kumwe\Sequence
ownership:
  responsibility: 'Portable document numbering: format, reset and scope values, allocator port and one
    refusal.'
  non_responsibilities:
  - 'Any allocator implementation: counter store, transaction, locks, fencing, deadlock retry and unique
    constraints.'
  - 'Business numbering policy: which documents are numbered, what an invoice or stock number is, and
    repair.'
  - 'A fiscal calendar: a fiscal-period key is a posting period the host declared; the package only renders
    it.'
  - 'Site and organization authority: the record scope a counter is keyed by is resolved and enforced
    by the host.'
  - 'Container registration: values, port and refusal are constructed directly; the host binds its adapter.'
  - Real-database evidence, which the host proves on the engines it supports.
  allowed_dependency_ceiling: []
  implementation_owner: kumwe/sequence
  next_consumer: kumwe/app
  public_manifests:
  - path: resources/public-api/v1.json
    sha256: 01f26a717405438d10bd76e33dda4edaae16fa03f3976dc26d8ccde2a30c1964
  - path: resources/capabilities/v1.json
    sha256: 13c07a238124b08486f53e3d4c7dccd7a0990d95b40ec017c302acc2357c9ea7
  - path: resources/service-map/v1.json
    sha256: 34ee30e7c49f707bafaa0a7e5d6d1964f32af98124a396424ecc678aae266ed0
  intentionally_excluded:
  - DoctrineBusinessNumberSequenceAllocator stays in App; it owns the counter row, its lock and compare-and-set
  - BusinessRecordTemporarilyUnavailable stays in App; it is the App's retryable record exception, not
    the port's
  - The share() binding in src/Kernel/ContainerFactory.php stays in App; composition is host authority
  - BusinessDefinitionValidator::validateSequence() stays in App; closed-field, column-width and scope-mode
    rules
  - The posting-period calendar and the fiscal-period key resolution in BusinessRecordService stay in
    App
  - BusinessNumberSequenceMigration and the business_number_sequences table stay in App; storage is the
    host's
  - Retry, lock-wait, deadlock and replay policy stay in App; they are policy around the port
framework_php:
  composer_package: kumwe/sequence
  canonical_namespace: Kumwe\Sequence
  public_api_manifest: resources/public-api/v1.json
  capability_manifest: resources/capabilities/v1.json
  service_map: resources/service-map/v1.json
  extracted_symbols:
  - old_fqcn: Kumwe\App\BusinessDefinition\Domain\NumberSequenceFormat
    new_fqcn: Kumwe\Sequence\Value\NumberSequenceFormat
    source_path: src/BusinessDefinition/Domain/NumberSequenceFormat.php
    target_path: src/Value/NumberSequenceFormat.php
    kind: class
    public_methods:
    - fromConfiguration
    - counter
    - render
    public_properties:
    - scope
    - reset
    - prefix
    - padding
    - timezone
    public_constants:
    - MAXIMUM_PADDING
    - MAXIMUM_PREFIX
    - MAXIMUM_LENGTH
    exceptions:
    - InvalidArgumentException
    serialization_contract: null
    compatibility: '0.2.0 correction (D13): counter() propagates the reserved organization marker refusal'
  - old_fqcn: Kumwe\App\BusinessDefinition\Domain\NumberSequenceReset
    new_fqcn: Kumwe\Sequence\Value\NumberSequenceReset
    source_path: src/BusinessDefinition/Domain/NumberSequenceReset.php
    target_path: src/Value/NumberSequenceReset.php
    kind: enum
    public_methods:
    - key
    public_properties: []
    public_constants:
    - Never
    - Yearly
    - Monthly
    - FiscalPeriod
    exceptions:
    - InvalidArgumentException
    serialization_contract: 'string-backed enum serialized as its value: never, yearly, monthly, fiscal-period'
    compatibility: preserved
  - old_fqcn: Kumwe\App\BusinessDefinition\Domain\NumberSequenceScope
    new_fqcn: Kumwe\Sequence\Value\NumberSequenceScope
    source_path: src/BusinessDefinition/Domain/NumberSequenceScope.php
    target_path: src/Value/NumberSequenceScope.php
    kind: enum
    public_methods:
    - key
    public_properties: []
    public_constants:
    - Site
    - Organization
    exceptions:
    - InvalidArgumentException
    serialization_contract: string-backed enum; serializes as its backing value (site, organization)
    compatibility: '0.2.0 correction (D13): organization identifier ''-'' is refused; other keys are unchanged'
  - old_fqcn: Kumwe\App\BusinessRecord\Application\BusinessNumberSequenceAllocator
    new_fqcn: Kumwe\Sequence\Contract\NumberSequenceAllocator
    source_path: src/BusinessRecord/Application/BusinessNumberSequenceAllocator.php
    target_path: src/Contract/NumberSequenceAllocator.php
    kind: interface
    public_methods:
    - allocate
    public_properties: []
    public_constants: []
    exceptions:
    - Kumwe\Sequence\Exception\NumberSequenceUnavailable
    serialization_contract: null
    compatibility: 'clean break (D1): @throws is NumberSequenceUnavailable, not BusinessRecordTemporarilyUnavailable'
  consumers:
    app_code:
    - src/BusinessDefinition/Application/BusinessDefinitionValidator.php
    - src/BusinessRecord/Application/BusinessRecordService.php
    - src/BusinessRecord/Application/RecordValueCodec.php
    - src/BusinessRecord/Infrastructure/Persistence/DoctrineBusinessNumberSequenceAllocator.php
    - src/BusinessSchema/Infrastructure/Schema/CanonicalDefinitionPhysicalSchemaCompiler.php
    configuration_and_di:
    - src/Kernel/ContainerFactory.php
    reflection_and_string_references:
    - tests/Architecture/ClientAssertedInstantBoundaryTest.php (lists the port's App path among its inspected
      files)
    - docs/architecture/governance/core-growth-baseline.json (baseline entries for the four old FQCNs)
    - src/BusinessRecord/Application/RecordRuleValidator.php (a documentation block names the old port)
    - src/Infrastructure/Persistence/Migration/BusinessNumberSequenceMigration.php (prose names the adapter
      only)
    fixtures_and_examples:
    - tests/Unit/BusinessDefinition/Domain/NumberSequenceFormatTest.php
    - tests/Unit/BusinessDefinition/Application/AllocatedNumberFieldRuleTest.php
    - tests/Unit/BusinessDefinition/Application/FiscalPeriodSequenceCoherenceTest.php
    - tests/Unit/BusinessDefinition/Application/NumberSequenceScopeCoherenceTest.php
    - tests/Integration/BusinessRecord/BusinessNumberSequenceContentionIntegrationTest.php
    - tests/Integration/BusinessRecord/BusinessNumberSequenceHotPathIntegrationTest.php
    - tests/Integration/BusinessRecord/BusinessNumberSequenceIdentityIntegrationTest.php
    - tests/Integration/BusinessRecord/BusinessRecordDeadlockIntegrationTest.php
    - tests/Integration/BusinessRecord/FiscalPeriodSequenceIntegrationTest.php
    - tests/Integration/Persistence/TransactionBoundaryEngineIntegrationTest.php
    external: []
  dependency_injection:
    mode: direct
    provider: null
    factories: []
    aliases: []
    service_lifetimes:
    - 'Kumwe\Sequence\Contract\NumberSequenceAllocator: request-supplied (bound to one connection by the
      host)'
    - 'Kumwe\Sequence\Value\NumberSequenceFormat: value, built per declaration; immutable and shareable'
    - 'Kumwe\Sequence\Value\NumberSequenceReset, NumberSequenceScope: enum cases; no lifetime'
    - 'Kumwe\Sequence\Exception\NumberSequenceUnavailable: thrown per refusal; never a service'
    configuration_keys: []
    provider_absence_reason: 'Values, a port and a refusal, no service: the host binds its adapter to
      the port FQCN.'
native_cpp: null
php_extension: null
tests:
  moved_or_added:
  - tests/Case/NumberSequenceFormatTest.php
  - tests/Case/NumberSequenceResetTest.php
  - tests/Case/NumberSequenceScopeTest.php
  - tests/Case/NumberSequenceAllocatorContractTest.php
  - tests/Case/NumberSequenceUnavailableTest.php
  - tests/Case/ManifestsTest.php
  - tests/Case/ArchitectureTest.php
  - tests/Case/ExamplesTest.php
  - tests/Case/DocumentationTest.php
  - tests/Support/ReferenceNumberSequenceAllocator.php
  remain_in_app_or_consumer:
  - tests/Integration/BusinessRecord/BusinessNumberSequenceContentionIntegrationTest.php
  - tests/Integration/BusinessRecord/BusinessNumberSequenceHotPathIntegrationTest.php
  - tests/Integration/BusinessRecord/BusinessNumberSequenceIdentityIntegrationTest.php
  - tests/Integration/BusinessRecord/BusinessRecordClientReferenceIntegrationTest.php
  - tests/Integration/BusinessRecord/BusinessRecordDeadlockIntegrationTest.php
  - tests/Integration/BusinessRecord/FiscalPeriodSequenceIntegrationTest.php
  - tests/Integration/Persistence/TransactionBoundaryEngineIntegrationTest.php
  - tests/Unit/BusinessDefinition/Application/AllocatedNumberFieldRuleTest.php
  - tests/Unit/BusinessDefinition/Application/FiscalPeriodSequenceCoherenceTest.php
  - tests/Unit/BusinessDefinition/Application/NumberSequenceScopeCoherenceTest.php
  - tests/Architecture/ClientAssertedInstantBoundaryTest.php
  split_tests:
  - 'BusinessNumberSequenceIdentityIntegrationTest.php: its CoversClass of the two values is dropped;
    the run stays'
  - 'FiscalPeriodSequenceIntegrationTest.php: its CoversClass of Format and Reset is dropped; the calendar
    run stays'
  prohibited_duplicates:
  - tests/Unit/BusinessDefinition/Domain/NumberSequenceFormatTest.php
  corpora: []
documentation:
  charter: CHARTER.md
  readme: README.md
  public_api: docs/public-api.md
  architecture: docs/architecture.md
  integration_or_consumer: docs/integration.md
  examples:
  - examples/allocate-and-render.php
  - examples/README.md
  changelog_record: 'CHANGELOG.md ## 0.2.1'
release_expectations:
  version_policy: SemVer, exact pins while pre-1.0; the newest CHANGELOG.md heading (## 0.2.1) is the
    release record
  expected_artifact_types:
  - Composer dist archive of the tag release-on-record creates from the changelog heading, then Packagist
  required_checks:
  - composer check (validate, lint, docs, architecture, manifests, autoload smoke, example, cs, analyse,
    test)
  - 'composer clean-consumer (the last step of composer check: built archive, no-dev install, smoke, example)'
  - no-dev classmap-authoritative install in the checkout with autoload smoke and example
  - composer audit --abandoned=fail
  - 'GitHub Actions: Sequence CI on the pull request head, Release on record on main'
  required_registry_or_installer: Packagist
  required_external_attestation: true
governance:
  completion_claim: false
decisions:
- 'D1: the port declares the package-owned NumberSequenceUnavailable, the one authorised clean break'
- 'D2: value and port package with no ConfigProvider, factory or alias; the host binds its adapter to
  the port FQCN'
- 'D3: every constant, grammar, default, message, signature and parameter name is the App''s; @since restarts
  at 0.1.0'
- 'D9: the suite is the dependency-free runner (tests/run.php), the pattern kumwe/conversion uses'
- 'D10: no allocator ships in src/, not even a test double; the in-memory reference lives under tests/
  and examples/'
- 'D11: the App''s unit corpus for the three values is replayed here case by case and extended'
- 'D12: the refusal''s message, code 0 and chained previous are the fixed identity a retry policy and
  a log see'
- 'D13: 0.2.0 rejects reserved organization key ''-'' to enforce disjoint scopes; supersedes D3 only for
  that input'
blockers: []
consumer_contract:
  permitted_only_when:
  - The selected published package has independent source/archive/manifest and clean-consumer verification.
  - Current Core consumers and test ownership have been compared with the recorded source baseline.
  consumer_repository: kumwe/app
  dependency_or_native_change: composer require kumwe/sequence:<verified>; exact pin; Composer regenerates
    the lock
  namespace_or_api_replacements:
  - Kumwe\App\BusinessDefinition\Domain\NumberSequenceFormat -> Kumwe\Sequence\Value\NumberSequenceFormat
  - Kumwe\App\BusinessDefinition\Domain\NumberSequenceReset -> Kumwe\Sequence\Value\NumberSequenceReset
  - Kumwe\App\BusinessDefinition\Domain\NumberSequenceScope -> Kumwe\Sequence\Value\NumberSequenceScope
  - Kumwe\App\BusinessRecord\Application\BusinessNumberSequenceAllocator is retired; its replacement follows
  - the port is Kumwe\Sequence\Contract\NumberSequenceAllocator, same signature, parameter names and order
  - the adapter throws NumberSequenceUnavailable instead of BusinessRecordTemporarilyUnavailable
  - allocateNumbers() catches NumberSequenceUnavailable and rethrows BusinessRecordTemporarilyUnavailable
  files_to_update:
  - composer.json
  - src/Kernel/ContainerFactory.php
  - src/BusinessDefinition/Application/BusinessDefinitionValidator.php
  - src/BusinessRecord/Application/BusinessRecordService.php
  - src/BusinessRecord/Application/RecordValueCodec.php
  - src/BusinessRecord/Application/RecordRuleValidator.php
  - src/BusinessRecord/Infrastructure/Persistence/DoctrineBusinessNumberSequenceAllocator.php
  - src/BusinessSchema/Infrastructure/Schema/CanonicalDefinitionPhysicalSchemaCompiler.php
  - tests/Architecture/ClientAssertedInstantBoundaryTest.php
  - tests/Unit/BusinessDefinition/Application/AllocatedNumberFieldRuleTest.php
  - tests/Unit/BusinessDefinition/Application/FiscalPeriodSequenceCoherenceTest.php
  - tests/Unit/BusinessDefinition/Application/NumberSequenceScopeCoherenceTest.php
  - tests/Integration/BusinessRecord/BusinessNumberSequenceContentionIntegrationTest.php
  - tests/Integration/BusinessRecord/BusinessNumberSequenceHotPathIntegrationTest.php
  - tests/Integration/BusinessRecord/BusinessNumberSequenceIdentityIntegrationTest.php
  - tests/Integration/BusinessRecord/BusinessRecordDeadlockIntegrationTest.php
  - tests/Integration/BusinessRecord/FiscalPeriodSequenceIntegrationTest.php
  - tests/Integration/Persistence/TransactionBoundaryEngineIntegrationTest.php
  - docs/architecture/layers.json
  - docs/architecture/capability-index.md
  - docs/architecture/governance/core-growth-baseline.json
  - docs/quality/baseline.json
  - CHANGELOG.md
  files_to_remove:
  - src/BusinessDefinition/Domain/NumberSequenceFormat.php
  - src/BusinessDefinition/Domain/NumberSequenceReset.php
  - src/BusinessDefinition/Domain/NumberSequenceScope.php
  - src/BusinessRecord/Application/BusinessNumberSequenceAllocator.php
  tests_to_remove:
  - tests/Unit/BusinessDefinition/Domain/NumberSequenceFormatTest.php
  tests_to_retain_or_add:
  - tests/Integration/BusinessRecord/BusinessNumberSequenceContentionIntegrationTest.php
  - tests/Integration/BusinessRecord/BusinessNumberSequenceHotPathIntegrationTest.php
  - tests/Integration/BusinessRecord/BusinessNumberSequenceIdentityIntegrationTest.php
  - tests/Integration/BusinessRecord/BusinessRecordClientReferenceIntegrationTest.php
  - tests/Integration/BusinessRecord/BusinessRecordDeadlockIntegrationTest.php
  - tests/Integration/BusinessRecord/FiscalPeriodSequenceIntegrationTest.php
  - tests/Integration/Persistence/TransactionBoundaryEngineIntegrationTest.php
  - tests/Unit/BusinessDefinition/Application/AllocatedNumberFieldRuleTest.php
  - tests/Unit/BusinessDefinition/Application/FiscalPeriodSequenceCoherenceTest.php
  - tests/Unit/BusinessDefinition/Application/NumberSequenceScopeCoherenceTest.php
  - tests/Architecture/ClientAssertedInstantBoundaryTest.php with the port's path replaced by the adapter's
  - 'add: the adapter raises NumberSequenceUnavailable with the driver failure chained (contention integration
    test)'
  - 'add: BusinessRecordService translates NumberSequenceUnavailable into BusinessRecordTemporarilyUnavailable'
  di_or_provisioning_changes:
  - keep the share() binding in src/Kernel/ContainerFactory.php keyed by the package port FQCN; no alias
    or fallback
  - 'no ConfigProvider to register: the package ships none'
  capability_index_changes:
  - composer kumwe:capability-index adds the v2-manifested kumwe/sequence entry with its four capabilities
  - 'docs/architecture/layers.json: add Kumwe\Sequence to first_party_namespaces'
  - 'docs/architecture/layers.json: classify Kumwe\Sequence\Value, Contract and Exception as shared'
  - composer kumwe:core-growth-record removes the four retired FQCNs from the baseline
  changelog_and_evidence_changes:
  - CHANGELOG.md entry citing NRM-2026-003 (or the next free NRM number, D6) and the App pull request
  - docs/architecture/non-roadmap/NRM-2026-003.yaml, this migration's non-roadmap record
  - docs/architecture/migrations/KUMWE-MIG-2026-002.yaml with the installed release-record digest
  - docs/architecture/migrations/change-sets/KUMWE-CS-2026-002.yaml at state app-pr-ready
  - docs/architecture/migrations/trains/<train id>.yaml, a single-PR train (D-GOV-8)
  - docs/architecture/migrations/evidence/KUMWE-MIG-2026-002/RELEASE-ATTESTATION.yaml copied unchanged
  verification_commands:
  - composer qa
  - composer kumwe:capability-index && composer kumwe:capability-index-check
  - composer kumwe:core-growth-record && composer kumwe:core-growth-check
  - composer baseline:record
  - composer test:unit
  - the merge workflow's MariaDB, MySQL and PostgreSQL matrix over tests/Integration/BusinessRecord
  - the same matrix over tests/Integration/Persistence/TransactionBoundaryEngineIntegrationTest.php
  - grep -rn 'BusinessNumberSequenceAllocator\|Domain.NumberSequence' src tests config bootstrap docs
---

# Release contract

## Package contract

This record preserves exact source provenance, manifest identities and consumer qualification requirements.
Migration/change-set IDs are stable evidence references. The [Core contract](integration.md) defines the current
storage, authority and transaction boundary independently of an implementation branch or rollout status.

## Public API and responsibility

The package owns numbering declarations, reset/scope values, counter coordinates, rendering, allocator port and
NumberSequenceUnavailable. It ships no allocator implementation. Core owns storage, locking, transaction/retry
policy, authority and fiscal calendars. See [the public API](public-api.md).

## Dependencies and semantic inputs

PHP is the only runtime dependency. The recorded Core source commit is historical provenance. Portable behavior
and public manifests are package-owned; real-database evidence remains with the host that owns its store.

## Consumer contract

Core supplies an allocator for its connection and active transaction, resolves trusted scope coordinates and
translates the package refusal to its own retryable application error. Use exact package pins, canonical names
and the capability index. The source inventory above is a drift-review map, not a current Core progress report.

## Test ownership

Package tests own grammar, bounds, keys, rendering, port shape/refusals and public manifests. Core proves first-use
races, contention, rollback, idempotent replay, driver error classification, validator rules, fiscal calendars and
composition on each supported database. Remove implementation-only duplicates with their retired implementation.
See [test ownership](test-ownership.md).

## Consumer verification

Follow [releasing](releasing.md) for source/tag, archive, registry, manifest and no-dev consumer verification.
Publication and independent downstream qualification are distinct evidence states. Native database durability
cannot be inferred from the package's in-memory reference allocator.

## Compatibility and drift

Review current source against the recorded baseline before replacing imports and exception mapping. Preserve
Core retry classification and database tests. Since 0.2.0 the reserved organization key `-` is refused so it cannot
share the site-wide counter. Never introduce a production fallback or widen declaration grammar in Core.

## Validation

Run `composer check` for PHP syntax/columns, member docs, architecture, manifests, strict analysis, behavior tests,
release fixtures and a built archive installed in an isolated no-dev Composer consumer. The archive must contain
this record, public documentation, manifests, runtime source and the shipped example.
