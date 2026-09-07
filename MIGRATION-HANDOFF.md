---
schema: kumwe-migration-handoff/v2
artifact_kind: framework_php
migration_id: KUMWE-MIG-2026-002
change_set: KUMWE-CS-2026-002
state: draft_pr_open
source:
  app:
    repository: https://github.com/kumwe/app
    baseline_commit: "960ce8ec00cf724a7cae03e5ba09c4852c9ab54e"
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
    capability_index_sha256: "87ded886f35f74878ca9eb8db4c36e23d681c4a49891f76dfc3210f385a7ce39"
  semantic_inputs: []
  examined_dependencies:
    - "kumwe/conversion 0.1.2 (installed, legacy-unmanifested); decimal and conversion semantics, no numbering"
    - "kumwe/extension-sdk 0.2.4 (installed, legacy-unmanifested); extension contracts, no numbering or counter"
    - "kumwe/producer 0.2.0 (installed, legacy-unmanifested); Studio wire operations, no numbering or counter"
    - "no Kumwe dependency selected; the allowed ceiling is None and the runtime requirement is php ^8.5 alone"
  active_related_pull_requests:
    - "https://github.com/kumwe/transaction/pull/1 (KUMWE-MIG-2026-001, Phase 1, ready for review)"
    - "https://github.com/kumwe/secret-envelope/pull/1 (KUMWE-MIG-2026-003, Phase 1, draft)"
    - "https://github.com/kumwe/access-context/pull/1 (KUMWE-MIG-2026-004, Phase 1, draft)"
target:
  repository: https://github.com/kumwe/sequence
  artifact_identity: "kumwe/sequence (Composer library)"
  canonical_namespace_or_abi: Kumwe\Sequence
  branch: fix/unified-package-release
  pull_request: "https://github.com/kumwe/sequence/pull/2"
ownership:
  responsibility: "Portable document numbering: format, reset and scope values, allocator port and one refusal."
  non_responsibilities:
    - "Any allocator implementation: counter store, transaction, locks, fencing, deadlock retry and unique constraints."
    - "Business numbering policy: which documents are numbered, what an invoice or stock number is, and repair."
    - "A fiscal calendar: a fiscal-period key is a posting period the host declared; the package only renders it."
    - "Site and organization authority: the record scope a counter is keyed by is resolved and enforced by the host."
    - "Container registration: values, port and refusal are constructed directly; the host binds its adapter."
    - "Real-database evidence, which the host proves on the engines it supports."
  allowed_dependency_ceiling: []
  implementation_owner: kumwe/sequence
  next_consumer: kumwe/app
  public_manifests:
    - path: resources/public-api/v1.json
      sha256: "01f26a717405438d10bd76e33dda4edaae16fa03f3976dc26d8ccde2a30c1964"
    - path: resources/capabilities/v1.json
      sha256: "13c07a238124b08486f53e3d4c7dccd7a0990d95b40ec017c302acc2357c9ea7"
    - path: resources/service-map/v1.json
      sha256: "34ee30e7c49f707bafaa0a7e5d6d1964f32af98124a396424ecc678aae266ed0"
  intentionally_excluded:
    - "DoctrineBusinessNumberSequenceAllocator stays in App; it owns the counter row, its lock and compare-and-set"
    - "BusinessRecordTemporarilyUnavailable stays in App; it is the App's retryable record exception, not the port's"
    - "The share() binding in src/Kernel/ContainerFactory.php stays in App; composition is host authority"
    - "BusinessDefinitionValidator::validateSequence() stays in App; closed-field, column-width and scope-mode rules"
    - "The posting-period calendar and the fiscal-period key resolution in BusinessRecordService stay in App"
    - "BusinessNumberSequenceMigration and the business_number_sequences table stay in App; storage is the host's"
    - "Retry, lock-wait, deadlock and replay policy stay in App; they are policy around the port"
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
      compatibility: "0.2.0 correction (D13): counter() propagates the reserved organization marker refusal"
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
      serialization_contract: "string-backed enum serialized as its value: never, yearly, monthly, fiscal-period"
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
      serialization_contract: "string-backed enum; serializes as its backing value (site, organization)"
      compatibility: "0.2.0 correction (D13): organization identifier '-' is refused; other keys are unchanged"
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
      compatibility: "clean break (D1): @throws is NumberSequenceUnavailable, not BusinessRecordTemporarilyUnavailable"
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
      - "tests/Architecture/ClientAssertedInstantBoundaryTest.php (lists the port's App path among its inspected files)"
      - "docs/architecture/governance/core-growth-baseline.json (baseline entries for the four old FQCNs)"
      - "src/BusinessRecord/Application/RecordRuleValidator.php (a documentation block names the old port)"
      - "src/Infrastructure/Persistence/Migration/BusinessNumberSequenceMigration.php (prose names the adapter only)"
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
      - "Kumwe\\Sequence\\Contract\\NumberSequenceAllocator: request-supplied (bound to one connection by the host)"
      - "Kumwe\\Sequence\\Value\\NumberSequenceFormat: value, built per declaration; immutable and shareable"
      - "Kumwe\\Sequence\\Value\\NumberSequenceReset, NumberSequenceScope: enum cases; no lifetime"
      - "Kumwe\\Sequence\\Exception\\NumberSequenceUnavailable: thrown per refusal; never a service"
    configuration_keys: []
    provider_absence_reason: "Values, a port and a refusal, no service: the host binds its adapter to the port FQCN."
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
    - "BusinessNumberSequenceIdentityIntegrationTest.php: its CoversClass of the two values is dropped; the run stays"
    - "FiscalPeriodSequenceIntegrationTest.php: its CoversClass of Format and Reset is dropped; the calendar run stays"
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
  changelog_record: "CHANGELOG.md ## 0.2.1"
release_expectations:
  version_policy: "SemVer, exact pins while pre-1.0; the newest CHANGELOG.md heading (## 0.2.1) is the release record"
  expected_artifact_types:
    - "Composer dist archive of the tag release-on-record creates from the changelog heading, then Packagist"
  required_checks:
    - "composer check (validate, lint, docs, architecture, manifests, autoload smoke, example, cs, analyse, test)"
    - "composer clean-consumer (the last step of composer check: built archive, no-dev install, smoke, example)"
    - "no-dev classmap-authoritative install in the checkout with autoload smoke and example"
    - "composer audit --abandoned=fail"
    - "GitHub Actions: Sequence CI on the pull request head, Release on record on main"
  required_registry_or_installer: Packagist
  required_external_attestation: true
next_task:
  phase_name: "Phase 2 App adoption of kumwe/sequence"
  permitted_only_when:
    - "a human has merged this pull request to main and Release on record has published the recorded version"
    - "a fresh session produced RELEASE-ATTESTATION.yaml with status verified for the published kumwe/sequence"
    - "the App governance bootstrap (NRM-2026-001) is merged on kumwe/app master, as it is at the baseline"
    - "the extracted symbols and their tests were diffed between the baseline commit and current master (D-GOV-7)"
  consumer_repository: kumwe/app
  dependency_or_native_change: "composer require kumwe/sequence:<verified>; exact pin; Composer regenerates the lock"
  namespace_or_api_replacements:
    - "Kumwe\\App\\BusinessDefinition\\Domain\\NumberSequenceFormat -> Kumwe\\Sequence\\Value\\NumberSequenceFormat"
    - "Kumwe\\App\\BusinessDefinition\\Domain\\NumberSequenceReset -> Kumwe\\Sequence\\Value\\NumberSequenceReset"
    - "Kumwe\\App\\BusinessDefinition\\Domain\\NumberSequenceScope -> Kumwe\\Sequence\\Value\\NumberSequenceScope"
    - "Kumwe\\App\\BusinessRecord\\Application\\BusinessNumberSequenceAllocator is retired; its replacement follows"
    - "the port is Kumwe\\Sequence\\Contract\\NumberSequenceAllocator, same signature, parameter names and order"
    - "the adapter throws NumberSequenceUnavailable instead of BusinessRecordTemporarilyUnavailable"
    - "allocateNumbers() catches NumberSequenceUnavailable and rethrows BusinessRecordTemporarilyUnavailable"
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
    - "tests/Architecture/ClientAssertedInstantBoundaryTest.php with the port's path replaced by the adapter's"
    - "add: the adapter raises NumberSequenceUnavailable with the driver failure chained (contention integration test)"
    - "add: BusinessRecordService translates NumberSequenceUnavailable into BusinessRecordTemporarilyUnavailable"
  di_or_provisioning_changes:
    - "keep the share() binding in src/Kernel/ContainerFactory.php keyed by the package port FQCN; no alias or fallback"
    - "no ConfigProvider to register: the package ships none"
  capability_index_changes:
    - "composer kumwe:capability-index adds the v2-manifested kumwe/sequence entry with its four capabilities"
    - "docs/architecture/layers.json: add Kumwe\\Sequence to first_party_namespaces"
    - "docs/architecture/layers.json: classify Kumwe\\Sequence\\Value, Contract and Exception as shared"
    - "composer kumwe:core-growth-record removes the four retired FQCNs from the baseline"
  changelog_and_evidence_changes:
    - "CHANGELOG.md entry citing NRM-2026-003 (or the next free NRM number, D6) and the App pull request"
    - "docs/architecture/non-roadmap/NRM-2026-003.yaml, this migration's non-roadmap record"
    - "docs/architecture/migrations/KUMWE-MIG-2026-002.yaml with handoff_sha256 of the installed handoff"
    - "docs/architecture/migrations/change-sets/KUMWE-CS-2026-002.yaml at state app-pr-ready"
    - "docs/architecture/migrations/trains/<train id>.yaml, a single-PR train (D-GOV-8)"
    - "docs/architecture/migrations/evidence/KUMWE-MIG-2026-002/RELEASE-ATTESTATION.yaml copied unchanged"
  verification_commands:
    - "composer qa"
    - "composer kumwe:capability-index && composer kumwe:capability-index-check"
    - "composer kumwe:core-growth-record && composer kumwe:core-growth-check"
    - "composer baseline:record"
    - "composer test:unit"
    - "the merge workflow's MariaDB, MySQL and PostgreSQL matrix over tests/Integration/BusinessRecord"
    - "the same matrix over tests/Integration/Persistence/TransactionBoundaryEngineIntegrationTest.php"
    - "grep -rn 'BusinessNumberSequenceAllocator\\|Domain.NumberSequence' src tests config bootstrap docs"
concurrency:
  likely_conflict_files:
    - composer.json
    - composer.lock
    - src/Kernel/ContainerFactory.php
    - src/BusinessRecord/Application/BusinessRecordService.php
    - tests/Integration/Persistence/TransactionBoundaryEngineIntegrationTest.php
    - tests/Integration/BusinessRecord/BusinessNumberSequenceIdentityIntegrationTest.php
    - docs/architecture/layers.json
    - docs/architecture/capability-index.md
    - docs/architecture/governance/core-growth-baseline.json
    - docs/architecture/governance/legacy-packages.json
    - docs/architecture/non-roadmap
    - docs/quality/baseline.json
    - CHANGELOG.md
  related_migrations:
    - KUMWE-MIG-2026-001
    - KUMWE-MIG-2026-003
    - KUMWE-MIG-2026-004
  ownership_conflicts: []
  integration_train: null
  resolution_rule: semantic-preservation
governance:
  roadmap_source_sha256: a202155ef1a65f5ab293d4f8397ebf4ac430db7f1e877c776bbe7851e6fe18d8
  roadmap_refs: []
  non_roadmap_refs:
    - NRM-2026-003
  completion_claim: false
decisions:
  - "D1: the port declares the package-owned NumberSequenceUnavailable, the one authorised clean break (section 1)"
  - "D2: value and port package with no ConfigProvider, factory or alias; the host binds its adapter to the port FQCN"
  - "D3: every constant, grammar, default, message, signature and parameter name is the App's; @since restarts at 0.1.0"
  - "D4: runtime php ^8.5 alone; local gates ran on PHP 8.4.19 with --ignore-platform-req=php, CI proves 8.5"
  - "D5: the first release is 0.1.0, the newest CHANGELOG.md heading (D-GOV-6); no tag or digest is predicted"
  - "D6: NRM-2026-003 is allocated in migration-number order among four concurrent Phase 1 handoffs (section 6)"
  - "D7: the archive gate requires this handoff, so the pre-handoff head fails only that gate, by design"
  - "D8: roadmap_refs stay empty; Session 2 names sequence as enabling, the change set records refs"
  - "D9: the suite is the dependency-free runner (tests/run.php), the pattern kumwe/conversion uses"
  - "D10: no allocator ships in src/, not even a test double; the in-memory reference lives under tests/ and examples/"
  - "D11: the App's unit corpus for the three values is replayed here case by case and extended (section 5)"
  - "D12: the refusal's message, code 0 and chained previous are the fixed identity a retry policy and a log see"
  - "D13: 0.2.0 rejects reserved organization key '-' to enforce disjoint scopes; supersedes D3 only for that input"
blockers: []
---

## 1. Migration/implementation summary

The portable document-numbering vocabulary and the allocator port moved from Kumwe App to this package
under `Kumwe\Sequence\`: `Value\NumberSequenceFormat` (a numbered field's declaration read into a validated
value — scope, reset, prefix, padding, timezone — the two counter coordinates it composes for a record scope
and an instant, and the deterministic rendering of a reserved value), `Value\NumberSequenceReset` (`never`,
`yearly`, `monthly`, `fiscal-period`, with the period key an instant maps to in the declared zone),
`Value\NumberSequenceScope` (`site`, `organization`, with the closed scope-key grammar) and
`Contract\NumberSequenceAllocator` (reserve the next value of the counter five coordinates name, inside the
caller's own transaction). The three values keep the App's constants (`MAXIMUM_PADDING` 12,
`MAXIMUM_PREFIX` 16, `MAXIMUM_LENGTH` 36), the prefix grammar `[A-Z0-9/-]{0,16}`, the defaults (`site`,
`never`, empty prefix, padding 6, UTC), every `InvalidArgumentException` and its message, every signature,
parameter name and public property, and the documented semantics; the port keeps its one method, its six
parameters in the App's order and with the App's names, and its gapless-on-commit promise sentence by
sentence. The only changes are the namespace, package-neutral wording where the App's blocks named
`core.sequence`, `FieldDefinition`, `BusinessDefinitionValidator`, `BusinessRecordService`,
`PostingPeriodCalendar` or the `business_number_sequences` table, and `@since` restarting at `0.1.0`.

One deliberate correction, the clean break the repository brief authorises (D1): the App's port declared
`@throws BusinessRecordTemporarilyUnavailable`, the App's own retryable record exception. A storage-neutral
port cannot name a host's exception hierarchy, so the port now declares the new, package-owned
`Exception\NumberSequenceUnavailable` — a final `RuntimeException` with one fixed message, code `0` and the
translated driver failure kept as the chained previous exception. Nothing else about the promise changed:
the refusal still means "nothing was reserved, replay the whole command rather than guess". The App's
adapter raises the package refusal and the App's record service translates it back into
`BusinessRecordTemporarilyUnavailable` at the port boundary in Phase 2, so the App's retry policy, its
three-attempt replay and its 503-with-`Retry-After` mapping are untouched. The break is recorded in
`CHANGELOG.md` and `CHARTER.md` and pinned by `NumberSequenceAllocatorContractTest` (the `@throws` and the
replay wording on the port) and `NumberSequenceUnavailableTest` (identity, message, chaining).

Deliberately not moved: `DoctrineBusinessNumberSequenceAllocator` (it owns the counter row, the `FOR
UPDATE`, the seed-on-first-use, the compare-and-set and the contention classification, including the
PostgreSQL `55P03` case), `BusinessRecordTemporarilyUnavailable` (the App's retryable record exception,
raised by many App adapters for many reasons), the `share()` binding in `src/Kernel/ContainerFactory.php`
(composition is host authority), `BusinessDefinitionValidator::validateSequence()` and its neighbours (the
closed-field, column-width, scope-mode and posting-date rules read the package constants and cases and stay
host rules), the posting-period calendar and the fiscal-period key resolution in `BusinessRecordService`
(a fiscal key is a declared posting period the host owns; the package refuses to compose one, as the App's
enum already did), `BusinessNumberSequenceMigration` and the `business_number_sequences` table, and the
real-database evidence on MariaDB, MySQL and PostgreSQL. No Kumwe dependency was selected: the allowed
ceiling is None and nothing in the closure needs one. Dependency injection is direct: no `ConfigProvider`,
no factory, no alias, with the reason recorded in the service map. No allocator ships in `src/`, not even a
test double (D10): an allocator that exists in production code invites a fallback, so the in-memory
reference lives under `tests/Support/` and in the example only. Kumwe App is unchanged by this phase.

## 2. Public API and responsibility

Responsibility: portable document numbering — the format, reset and scope a numbered field declares, the
storage-neutral allocator port and its one refusal; the host supplies the adapter. Non-responsibilities are
listed in the front matter and in [`CHARTER.md`](CHARTER.md).

| Symbol | Capability |
| --- | --- |
| `Kumwe\Sequence\Value\NumberSequenceFormat` (final readonly class) | `sequence.format` |
| `Kumwe\Sequence\Value\NumberSequenceReset` (string-backed enum, four cases) | `sequence.reset` |
| `Kumwe\Sequence\Value\NumberSequenceScope` (string-backed enum, two cases) | `sequence.scope` |
| `Kumwe\Sequence\Contract\NumberSequenceAllocator` (interface, extension point) | `sequence.allocation` |
| `Kumwe\Sequence\Exception\NumberSequenceUnavailable` (final exception) | `sequence.allocation` |

Every public member — the three constants, the five read-only properties, the three format methods, the
two `key()` methods, the enum cases, `allocate()` and the refusal's constructor — is documented in
[`docs/public-api.md`](docs/public-api.md) with parameters, return, invariants, exceptions, side effects,
state, nullability, precision, transaction expectations, concurrency and an example, preceded by the
grammar-and-bounds table a host validates against; the reflected surface is pinned in
[`resources/public-api/v1.json`](resources/public-api/v1.json) with enum cases recorded under `constants`,
the capabilities in [`resources/capabilities/v1.json`](resources/capabilities/v1.json) and the provider
decision in [`resources/service-map/v1.json`](resources/service-map/v1.json). The manifest digests in the
front matter are the digests of those files as committed with this handoff; `composer manifests`
regenerates the public API manifest from reflection and refuses drift. [`README.md`](README.md) carries the
twelve sections the package standard requires; [`docs/architecture.md`](docs/architecture.md) the three
layers and the lane; [`docs/integration.md`](docs/integration.md) the host's publication rules, the record
command composition, the adapter obligations, the binding and the refusal translation;
[`docs/releasing.md`](docs/releasing.md) and [`docs/security.md`](docs/security.md) the release,
compatibility and security policies.

## 3. Capability reuse/semantic input review

Capability index inspected: `docs/architecture/capability-index.md` at the baseline commit, index digest
`87ded886f35f74878ca9eb8db4c36e23d681c4a49891f76dfc3210f385a7ce39` (three packages, all
legacy-unmanifested). Installed releases from `composer show --locked "kumwe/*"`: `kumwe/conversion 0.1.2`,
`kumwe/extension-sdk 0.2.4`, `kumwe/producer 0.2.0`. Their source trees under `vendor/kumwe/*/src` were
searched by responsibility and behaviour — `number sequence`, `numbering`, `document number`, `invoice
number`, `gapless`, `allocate`, `allocator`, `counter`, `scope key`, `period key`, `reset`, `padding`,
`prefix` — as well as by symbol name. No installed package owns document numbering, a counter, a reset
period or an allocator port. The only hits are unrelated: `kumwe/extension-sdk`'s `ExtensionTableNames`
("table-name allocator" for migrations), `kumwe/producer`'s `Wire\Dispatcher::scopeKey()` (a replay scope
for Studio wire operations) and prose in `kumwe/conversion`'s decimal arithmetic. The catalog (Kumwe-v2-05,
row 5) names this repository as the sole owner with an allowed ceiling of None, and `kumwe/business-definition`
(row 18) as a possible later dependent "only if closure proves it". No semantic input, corpus or upstream
handoff exists for this package; none is required. `kumwe/transaction` (pull request 1, the reference
Version 2 extraction) was consulted for structure and quality bar, not as a dependency: the port here
documents that an allocation joins the caller's transaction but does not type against the transaction
package, exactly as the App's port did not.

## 4. Consumer inventory

Recomputed at the baseline commit with `grep -rn` over `src/`, `tests/`, `config/`, `bootstrap/`,
`examples/`, `composer.json` and `docs/architecture/`, by the four FQCNs and by short name. Production
consumers (`consumers.app_code`): `BusinessDefinitionValidator` imports all three values (reads
`MAXIMUM_LENGTH`, `NumberSequenceScope::Organization` and `NumberSequenceReset::FiscalPeriod` and builds a
format at publication); `BusinessRecordService` imports the format and the reset, takes the port in its
constructor (same namespace, no import) and composes counter, allocation and rendering in
`allocateNumbers()`; `RecordValueCodec` and `CanonicalDefinitionPhysicalSchemaCompiler` read
`MAXIMUM_LENGTH` to bound the stored value and size the column (no migration reads it: the column is
compiled from the definition, so no migration file is a consumer); `DoctrineBusinessNumberSequenceAllocator`
implements the port and raises the App exception the port declared. `src/Kernel/ContainerFactory.php` binds
the port to the adapter with `share()` and resolves it into `BusinessRecordService`. Two files reference the
old names outside a `use` statement: `tests/Architecture/ClientAssertedInstantBoundaryTest.php` (its list of
inspected files names the port's App path beside the adapter's) and
`docs/architecture/governance/core-growth-baseline.json` (baseline entries for the four FQCNs); two
documentation blocks name the old port or the adapter by short name (`RecordRuleValidator`,
`BusinessNumberSequenceMigration`). `config/`, `bootstrap/`, `examples/` and `resources/` reference
nothing. Ten test files import a moved symbol (`consumers.fixtures_and_examples`): one unit test of the
values (the prohibited duplicate), three unit tests of the definition validator that read
`MAXIMUM_LENGTH`, and six integration tests that resolve the port from the container, cover the Doctrine
adapter or the values, or exercise the fiscal-period calendar. Four documents mention the types by short
name in prose (`docs/roadmap/README.md`, `docs/roadmap/decisions/0008-numbering-under-disconnection.md`,
`docs/roadmap/decisions/0011-create-is-the-numbering-transition.md`, `docs/qualification/gap-matrix.md`);
their wording is reviewed at adoption and the historical entries are left as history.

## 5. Test ownership

Moved or added to this package: the nine cases under `tests/Case/` and the reference allocator under
`tests/Support/`. `NumberSequenceFormatTest` replays every case of the App's
`tests/Unit/BusinessDefinition/Domain/NumberSequenceFormatTest.php` — the empty declaration, the declared
zone against UTC, the monthly key, the per-organization key and its refusal, the widest format, the
outgrown value, the unusable declarations, the zero value, the empty lifetime key, the fiscal declaration,
the fiscal refusal through `key()` and through `counter()` — and adds the null-defaults case, the bounds and
their sum, padding as a minimum width, the full prefix grammar, negative and `PHP_INT_MIN` values, a
declared fiscal key counting toward `MAXIMUM_LENGTH`, determinism and the read-only shape (D11).
`NumberSequenceResetTest` pins the case set, serialization, and the yearly and monthly keys at their local
boundaries in four zones, independent of the instant's offset and of the process default zone.
`NumberSequenceScopeTest` pins the case set, the fixed site marker, the verbatim organization key, the
empty-key refusal and identity-by-value. `NumberSequenceAllocatorContractTest` pins the port's shape (one
method, six required non-nullable parameters in the App's order and names, `int` return), its `@throws`
and its promise wording, and holds `ReferenceNumberSequenceAllocator` to contiguity from one, independent
counters per coordinate, a refusal that consumes nothing and composition with the format.
`NumberSequenceUnavailableTest` pins the refusal's ancestry, finality, message, code and chaining. The
remaining four cases hold the three manifests to the source tree, the changelog and the API document, prove
the architecture boundary and the release archive's file set, replay the shipped example, and check every
document's relative links and the README's required sections.

Retained in App: `BusinessNumberSequenceContentionIntegrationTest` (interleaved creates, lock waits and
first-use races on real engines), `BusinessNumberSequenceHotPathIntegrationTest` (statement counts and
timings of the adapter), `BusinessNumberSequenceIdentityIntegrationTest` (the counter tuple across sites,
definitions, fields, scopes and periods against the store), `BusinessRecordClientReferenceIntegrationTest`,
`BusinessRecordDeadlockIntegrationTest` (`RetryableException` to the App's retryable exception to a 503),
`FiscalPeriodSequenceIntegrationTest` (the posting-period calendar keying a fiscal counter),
`TransactionBoundaryEngineIntegrationTest` (the allocation rolling back with the command), the three unit
tests of `BusinessDefinitionValidator` that read `MAXIMUM_LENGTH`, and `ClientAssertedInstantBoundaryTest`.

Split: `BusinessNumberSequenceIdentityIntegrationTest` and `FiscalPeriodSequenceIntegrationTest` declare
`CoversClass` of the values beside the adapter; the coverage claim on the values moves here (they are now
vendor classes) while the database runs stay in App unchanged.

Prohibited duplicate after adoption: `tests/Unit/BusinessDefinition/Domain/NumberSequenceFormatTest.php`,
every case of which this package's suite replays; App must not keep a vendor value as its unit under test.

## 6. Next-task execution notes

1. Verify the published release and this handoff independently (Kumwe-v2-11) and confirm
   `RELEASE-ATTESTATION.yaml` reports `status: verified`; stop otherwise.
2. Run the drift check of section 7; route any portable change into a successor release first.
3. `composer require kumwe/sequence:<verified version>` in `kumwe/app` with the exact pin; let Composer
   regenerate `composer.lock`; never hand-edit it.
4. Replace the four names everywhere (`namespace_or_api_replacements`): the `use` statements in the five
   production files and the nine test files that import a moved symbol, the constructor type and the
   `@param` block of `BusinessRecordService`, the `implements` clause of the adapter, the binding in
   `src/Kernel/ContainerFactory.php`, and the path list in `ClientAssertedInstantBoundaryTest` (drop the
   retired port path; keep the adapter's). Delete the four files in `files_to_remove` and the unit test in
   `tests_to_remove`.
5. Apply the clean break at the port boundary: `DoctrineBusinessNumberSequenceAllocator::allocate()` throws
   `new NumberSequenceUnavailable($exception)` where it threw `new BusinessRecordTemporarilyUnavailable(...)`
   (its `LogicException` and `RuntimeException` faults are unchanged); `BusinessRecordService::allocateNumbers()`
   catches `NumberSequenceUnavailable` around the `allocate()` call and rethrows
   `new BusinessRecordTemporarilyUnavailable($refusal)`, so the retry loop and the delivery mapping see the
   exception they see today. In `BusinessNumberSequenceContentionIntegrationTest` the catches around direct
   adapter calls change to `NumberSequenceUnavailable`; catches around record-service commands stay. Add the
   two tests named in `tests_to_retain_or_add`.
6. Keep the `share()` binding in `ContainerFactory`, now keyed by
   `Kumwe\Sequence\Contract\NumberSequenceAllocator`; register no alias for the retired names and no
   fallback.
7. In `docs/architecture/layers.json` add `Kumwe\Sequence` to `first_party_namespaces` and classify
   `Kumwe\Sequence\Value`, `Kumwe\Sequence\Contract` and `Kumwe\Sequence\Exception` as `shared` by explicit
   longest-prefix rules; run `php tools/verify-dependency-graph.php`.
8. `composer kumwe:capability-index` (the entry is `v2-manifested`; its `handoff` digest is the sha256 of
   `vendor/kumwe/sequence/MIGRATION-HANDOFF.md`), then `composer kumwe:core-growth-record` to drop the four
   retired FQCNs, then `composer baseline:record` for the removed and added tests.
9. Read `docs/architecture/non-roadmap/` and take `NRM-2026-003` if it is still free; four Phase 1 handoffs
   were prepared concurrently against the same baseline, so if another adoption took it first, take the
   next free number and cite that one instead (D6, D-GOV-3). Write
   `docs/architecture/migrations/KUMWE-MIG-2026-002.yaml`,
   `docs/architecture/migrations/change-sets/KUMWE-CS-2026-002.yaml` (`app-pr-ready`), the non-roadmap
   record, the single-PR train record, and copy the attestation to
   `docs/architecture/migrations/evidence/KUMWE-MIG-2026-002/`; add the `CHANGELOG.md` entry citing the
   non-roadmap id and the App PR number. Claim no roadmap objective.
10. Run every command in `verification_commands`, including the three-engine integration matrix, and open
    the App PR against `master`; stop without merging.

## 7. Drift check

At Phase 2 start, in `kumwe/app`, compare the extracted symbols and their tests between the baseline and the
current target:

```text
git diff 960ce8ec00cf724a7cae03e5ba09c4852c9ab54e..origin/master -- \
  src/BusinessDefinition/Domain/NumberSequenceFormat.php \
  src/BusinessDefinition/Domain/NumberSequenceReset.php \
  src/BusinessDefinition/Domain/NumberSequenceScope.php \
  src/BusinessRecord/Application/BusinessNumberSequenceAllocator.php \
  src/BusinessRecord/Infrastructure/Persistence/DoctrineBusinessNumberSequenceAllocator.php \
  tests/Unit/BusinessDefinition/Domain/NumberSequenceFormatTest.php \
  tests/Integration/BusinessRecord tests/Integration/Persistence/TransactionBoundaryEngineIntegrationTest.php
```

A change to a constant, the grammar, a default, a message, a key, the rendering, the port's signature or
its documented promise is portable: port it to this package, release a successor version, verify it, and
adopt that version instead. A change to the Doctrine adapter, the validator's host rules, the binding, the
posting-period calendar or the database tests is App-specific and stays in App. Unrelated App changes are
ignored (D-GOV-7). Never delete a newer App implementation because an older snapshot was extracted. At
authoring time the same diff between `6f9e42cb59a84ba3ca523a70475cf4d7263c68e7` (the previous extraction
baseline) and this baseline was empty for every path above.

## 8. Validation recipe and observed local results

Recipe, from a clean clone of the pull-request branch:

```text
composer install
composer check
composer install --no-dev --classmap-authoritative && composer autoload:smoke && composer examples
```

Authoring environment: PHP 8.4.19 (the package declares `^8.5`; Composer ran with
`--ignore-platform-req=php` and the clean-consumer gate with
`KUMWE_CLEAN_CONSUMER_COMPOSER_ARGS=--ignore-platform-req=php`), Composer 2.8.12 with the development
packages installed from source because dist downloads are blocked there, PHPStan 2.2.12, PHP_CodeSniffer
4.0.4. Continuous integration runs the same lane on real PHP 8.5.

Observed on the tree that contains this handoff, before it was committed (the final tested head identity is
external to this file): `composer validate --strict` valid; lint 25 PHP files and every tracked file within
120 columns; member documentation complete (30 members, 6 files); architecture verified (5 source files);
manifests verified (5 public symbols, 4 capabilities, no provider with reason, release 0.1.0 documented);
autoload smoke 5 symbols; the example's output as documented; `phpcs` clean; PHPStan level max with strict
and deprecation rules clean; the suite 54 tests, 356 assertions; the clean-consumer gate green (archive
file set verified, no-dev classmap-authoritative install, smoke and example inside the archive); the no-dev
classmap-authoritative proof in the checkout green. Before this handoff existed the same lane was green
except the clean-consumer gate, which refused the archive for the missing `MIGRATION-HANDOFF.md` by design
(D7). The three manifests and this front matter validate against the Kumwe App governance schemas, and the
App's `PackageManifests` reader accepts the package as `v2-manifested` with the handoff in state
`draft_pr_open`. A gitleaks history scan could not run locally (no Docker); the tree carries no
configuration or credential material, and `composer audit --abandoned=fail` runs in CI.

## September 2026 extraction audit follow-up

This follow-up is prepared in [PR #2](https://github.com/kumwe/sequence/pull/2), with the release record
`CHANGELOG.md ## 0.2.0`. The source baseline, original extraction inventory and historical evidence
above remain provenance for the original work; this section records the successor's reviewed changes.
The front-matter target and public-manifest digests describe this successor, not the original PR.

The only production delta is `NumberSequenceScope::Organization->key('-')`: it now raises
`InvalidArgumentException` because `-` is already the site-wide counter marker. This closes the
documented collision-free scope-key guarantee. Its propagation through `NumberSequenceFormat::counter()`
is documented. This intentional refusal change uses 0.2.0 under the package's pre-1.0 compatibility policy.
Before adoption, inspect host organization identifiers and reject or migrate the reserved marker through
the host's authority and data-migration process; never silently merge or rename counters. All other
formatting, reset, allocation and refusal behavior remains the original extraction's behavior.

The package still has no runtime Composer dependency beyond PHP, no provider and no host implementation.
The improved consumer gate installs the exact built ZIP as a dependency of an otherwise empty Composer
project, verifies its installed archive, and exercises its shipped smoke and example exclusively through
the consumer's authoritative autoloader. Packagist is disabled for that dependency-free consumer.
The release workflow now uses one tested parser for pushed and tagged changelogs, including Unreleased.
Malformed headings fail closed; the complete check command also includes security audit.

App adoption remains a separate task after a human merge, automated publication and independent external
release attestation for this successor. The existing file-specific consumer, removal and retained-test
instructions above remain in force. No release identity, archive digest, database result or completed
roadmap objective is claimed by this embedded record.

Follow-up verification used PHP 8.5.10 with Composer 2.10.3 and no platform bypass: strict coding standards,
PHPStan level max with strict/deprecation rules, package tests and the real ZIP dependency consumer passed.
Observed results: 56 tests and 362 assertions; a 22-file installed archive with 5 public symbols.
The release parser passed twelve cases. Local security audit could not reach the advisory endpoint on one
attempt; GitHub Actions ran the audit successfully on the initial draft, and the final PR workflow reruns
the full gate. Its check result is external to this embedded handoff and must be green before review-ready.

## Enforced package test ownership

Portable behavior, boundary and conformance evidence is maintained in `tests/ownership.json`,
validated against the public API and actual test-runner discovery by `composer test:ownership`.
See `docs/test-ownership.md` for the future-change rule and the precise host boundary.
This follow-up changes package tests/tooling only; it does not authorize early App test deletion.

## Release automation successor candidate: 0.2.1

`CHANGELOG.md ## 0.2.1` records the candidate prepared by this change. It is not a published release or a
release attestation. The three public manifests and their front-matter digests describe this candidate;
the extraction inventory, App baseline and earlier follow-up evidence remain historical provenance.

Release verification uses the commit supplied by the push event after rebasing onto the default branch.
The release candidate does not embed a pull-request head identity as a test prerequisite. Publication
prerequisites are checked before changes to tags or releases; previously published versions are preserved.
The runtime source, public symbols, host responsibilities and package test ownership are unchanged.

App adoption still requires successful publication and independent external attestation of this successor.
No archive digest, published tag identity or successful hosted workflow result is asserted by this record.
