# Public API

Every stable public symbol of `kumwe/sequence`, member by member. The machine-readable form is
[`resources/public-api/v1.json`](../resources/public-api/v1.json), generated from reflection over `src/`
(enum cases recorded as constants typed as their enum) and held to this document by `composer manifests`:
a symbol or public member that is exported but not documented here fails the lane. The canonical namespace
is `Kumwe\Sequence\`; no type here has any other name.

Vocabulary used below:

- **Declaration** — the configuration map of one allocated-number field, as the host's field definition
  stores it.
- **Counter** — one contiguous run of values, identified by five coordinates: site, definition, field
  handle, scope key and period key. The definition with its field handle is the document type; the site is
  the legal entity; the scope key subdivides by organization; the period key subdivides by reset period.
- **Reserved value** — the integer an adapter handed out for one counter inside the caller's transaction.
- **Rendered number** — the text a person reads: prefix, period segment, hyphen, zero-padded value.
- **Adapter** — the host's implementation of `NumberSequenceAllocator`, bound to one connection.

## Grammar and bounds

| Declaration key | Grammar | Default | Bound |
| --- | --- | --- | --- |
| `scope` | `site`, `organization` (the backing values of `NumberSequenceScope`) | `site` | closed set |
| `reset` | `never`, `yearly`, `monthly`, `fiscal-period` (`NumberSequenceReset`) | `never` | closed set |
| `prefix` | zero or more of `A-Z`, `0-9`, `-`, `/` | `` (empty) | at most `MAXIMUM_PREFIX` (16) characters |
| `padding` | a whole number (PHP `int`, never text) | `6` | 1 to `MAXIMUM_PADDING` (12) |
| `timezone` | an IANA identifier the runtime knows | `UTC` | — |

| Key or value | Grammar |
| --- | --- |
| scope key | `-` for `site`; the organization identifier, verbatim and non-empty, for `organization` |
| period key | `` for `never`; `YYYY` for `yearly`; `YYYY-MM` for `monthly`; a host-declared posting-period key for `fiscal-period` |
| reserved value | an integer of one or more; a value that pads wider than `MAXIMUM_PADDING` digits is refused at rendering |
| rendered number | `prefix` `period` `-` `digits` (the hyphen only when a period segment is present); at most `MAXIMUM_LENGTH` (36) characters |

`MAXIMUM_LENGTH` is `MAXIMUM_PREFIX` + 8 + `MAXIMUM_PADDING`: the widest prefix, the widest calendar period
segment (`YYYY-MM`) with its separator, and the widest padded value. A declared fiscal-period key counts
toward it exactly as a calendar segment would, so a key longer than seven characters leaves less room for
the prefix.

## `Kumwe\Sequence\Value\NumberSequenceFormat`

| | |
| --- | --- |
| Kind | final readonly class; not an extension point |
| Stability | stable since 0.1.0 |
| File | `src/Value/NumberSequenceFormat.php` |
| Capability | `sequence.format` |
| State | immutable; five public read-only properties fixed at construction |
| Concurrency | safe to share; no internal state changes after construction |

**Responsibility.** Hold the half of the numbering promise a definition can state: which counter a field
draws from (scope and reset) and how a reserved value reads (prefix, padding, timezone). Every value is
validated at construction so an unusable declaration fails when it is published, never when the first
record is created.

### Constants

- `MAXIMUM_PADDING` — `int`, 12. Widest padding a counter may declare, chosen so the padded run stays inside
  a signed 64-bit value.
- `MAXIMUM_PREFIX` — `int`, 16. Longest literal prefix a number may carry.
- `MAXIMUM_LENGTH` — `int`, 36. Longest value `render()` can produce, and therefore the width a host must
  give the column that stores the number.

### Properties

All public, read-only, set by `fromConfiguration()`:

- `$scope` — `NumberSequenceScope`. Tenancy boundary the run is contiguous within.
- `$reset` — `NumberSequenceReset`. Calendar boundary the run restarts at.
- `$prefix` — `string`. Literal head of the rendered number, possibly empty.
- `$padding` — `int`. Minimum number of digits the value is left-padded to with zeroes.
- `$timezone` — `DateTimeZone`. Zone the reset boundary and period segment are judged in.

The constructor is private: a declaration is always read through the factory below, so an instance that
exists is one that was validated.

### `fromConfiguration(array $configuration): self`

**Responsibility.** Read a field's declared configuration into a validated declaration.

**Parameters.**

- `$configuration` — `array<string, scalar|list<scalar|null>|null>`, required. The field's configuration
  map. Every key is optional; a key that is absent or `null` takes its default (table above). A key that is
  present but unusable is refused, never defaulted over.

**Returns.** `self` — the validated declaration.

**Exceptions.** `InvalidArgumentException` when `scope` or `reset` is not a backed value of its enum, when
`prefix` is outside its grammar or longer than `MAXIMUM_PREFIX`, when `padding` is not an `int` in
1..`MAXIMUM_PADDING` (text such as `"6"` is refused), when `timezone` is unknown to the runtime, or when
`scope`, `reset`, `prefix` or `timezone` is present but not a string. The message names the offending key.

**Side effects and state mutation.** None; the input array is not modified.

**Nullability.** `$configuration` is required; its values may be `null`, which means "default".

**Precision and canonicalization.** The prefix is kept verbatim (no case folding, no trimming). The
timezone is canonicalized by `DateTimeZone`.

**Transaction expectations, concurrency.** Not applicable; pure construction.

**Example.**

```php
$format = NumberSequenceFormat::fromConfiguration([
    'scope' => 'organization', 'reset' => 'yearly', 'prefix' => 'INV-', 'padding' => 6,
    'timezone' => 'Africa/Windhoek',
]);
// NumberSequenceFormat::fromConfiguration(['reset' => 'quarterly']) throws InvalidArgumentException
```

### `counter(?string $organizationIdentifier, DateTimeImmutable $at): array`

**Responsibility.** Name the counter this declaration allocates from, for a record scope and an instant.

**Parameters.**

- `$organizationIdentifier` — `?string`. The organization the record was resolved to, or `null` when its
  definition's scope carries no organization dimension. Read only for an `organization` scope.
- `$at` — `DateTimeImmutable`, required. The instant the allocation is being made at. Its own offset is
  irrelevant; it is converted to the declared zone.

**Returns.** `array{scope: string, period: string}` — the scope key from `NumberSequenceScope::key()` and
the period key from `NumberSequenceReset::key()`, in that key order. With the site, definition and field
handle they identify exactly one counter.

**Exceptions.** `InvalidArgumentException` when the scope is `organization` and the identifier is `null`
or empty, or when the reset is `fiscal-period` — its period key is a posting period the host declared, so
the host composes the scope key through `NumberSequenceScope::key()` and supplies the period key itself.

**Side effects and state mutation.** None.

**Nullability.** `$organizationIdentifier` may be `null`; the return never is.

**Precision and canonicalization.** The instant is judged in `$timezone`, never in the process default
zone, so two replicas with different ambient zones name the same counter.

**Transaction expectations, concurrency.** Not applicable; pure.

**Example.**

```php
$format->counter('north', new DateTimeImmutable('2026-12-31T22:30:00+00:00'));
// ['scope' => 'north', 'period' => '2027'] — already 1 January in Windhoek
```

### `render(int $value, string $periodKey): string`

**Responsibility.** Turn a reserved value into the number a person reads on the document.

**Parameters.**

- `$value` — `int`, required. The reserved value; one or more.
- `$periodKey` — `string`, required. The period key from `counter()`, or the host-declared posting-period
  key for a fiscal reset; empty for a lifetime run.

**Returns.** `string` — `$prefix`, then the period key and a hyphen when the period key is non-empty, then
the value left-padded with zeroes to `$padding` digits (wider values render in full).

**Exceptions.** `InvalidArgumentException` when `$value` is below one, or when the rendered text would be
longer than `MAXIMUM_LENGTH` — the number is refused rather than truncated.

**Side effects and state mutation.** None. **Nullability.** Never null.

**Precision and canonicalization.** Padding is a minimum width, never a truncation. Prefix and period key
render verbatim; the separator is always `-`.

**Transaction expectations, concurrency.** Not applicable; a pure function of its arguments.

**Example.**

```php
$format->render(42, '2026');   // 'INV-2026-000042'
$format->render(1, '');        // 'INV-000001' — no separator without a period segment
$format->render(0, '2026');    // throws InvalidArgumentException
```

## `Kumwe\Sequence\Value\NumberSequenceReset`

| | |
| --- | --- |
| Kind | string-backed enum; not an extension point |
| Stability | stable since 0.1.0 |
| File | `src/Value/NumberSequenceReset.php` |
| Capability | `sequence.reset` |
| State | none; cases are singletons |
| Concurrency | safe to share |

**Responsibility.** The calendar boundary at which a run starts counting from one again — and therefore
how many counters a declaration really has. The period segment it produces is part of the number, so
changing the reset on a published definition changes what the numbers mean.

### Cases

- `Never` — `'never'`. One lifetime counter; the number never returns to one.
- `Yearly` — `'yearly'`. One counter per calendar year in the declared zone.
- `Monthly` — `'monthly'`. One counter per calendar month in the declared zone.
- `FiscalPeriod` — `'fiscal-period'`. One counter per posting period the host declared, containing the
  record's posting date. The key is the host's declared period key, resolved by the host from the posting
  date — never from the allocation instant — and it counts toward `MAXIMUM_LENGTH` like a calendar segment.

The backed case set is pinned by the suite and recorded in the public API manifest; a new case is a new
release. `from()`, `tryFrom()` and `cases()` are the engine's.

### `key(DateTimeImmutable $at, DateTimeZone $timezone): string`

**Responsibility.** Name the counter period an instant belongs to, in the declared zone.

**Parameters.**

- `$at` — `DateTimeImmutable`, required. The allocation instant, in any offset.
- `$timezone` — `DateTimeZone`, required. The zone the period boundary is judged in.

**Returns.** `string` — `''` for `Never`; `YYYY` for `Yearly`; `YYYY-MM` (zero-padded month) for
`Monthly`, all read from the instant converted to `$timezone`.

**Exceptions.** `InvalidArgumentException` for `FiscalPeriod`, always: no instant and zone can compute a
declared posting period's key, and an empty or guessed key would merge every fiscal run into the lifetime
counter.

**Side effects and state mutation.** None. **Nullability.** Never null.

**Precision and canonicalization.** The process default zone is never consulted; the instant's own offset
is irrelevant. The boundary is local midnight on 1 January (`Yearly`) or the first of the month
(`Monthly`) in `$timezone`.

**Transaction expectations, concurrency.** Not applicable; pure.

**Example.**

```php
NumberSequenceReset::Yearly->key(new DateTimeImmutable('2026-12-31T22:00:00+00:00'), new DateTimeZone('Africa/Windhoek'));
// '2027'
```

## `Kumwe\Sequence\Value\NumberSequenceScope`

| | |
| --- | --- |
| Kind | string-backed enum; not an extension point |
| Stability | stable since 0.1.0 |
| File | `src/Value/NumberSequenceScope.php` |
| Capability | `sequence.scope` |
| State | none; cases are singletons |
| Concurrency | safe to share |

**Responsibility.** The tenancy boundary a run is unique and contiguous within. A run is always at least
per site; `Organization` subdivides it per branch. The scope key is one of the five counter coordinates and
is composed from the record's own resolved scope, never from caller input.

### Cases

- `Site` — `'site'`. One counter per site; every record on the site shares the run.
- `Organization` — `'organization'`. One counter per organization branch within the site.

### `key(?string $organizationIdentifier): string`

**Responsibility.** Resolve the scope key for the organization a record was resolved to.

**Parameters.**

- `$organizationIdentifier` — `?string`. The organization identifier the host resolved, or `null` when the
  definition's scope carries no organization dimension.

**Returns.** `string` — `-` for `Site`, whatever the identifier; the identifier verbatim for
`Organization`. The site marker is fixed, so no organization identifier can be mistaken for it.

**Exceptions.** `InvalidArgumentException` for `Organization` with a `null` or empty identifier: an empty
key would merge every branch into one run. The identifier's own grammar is the host's; this package does not
constrain it beyond non-emptiness.

**Side effects, nullability, precision, transaction, concurrency.** None; the argument may be `null`; the
identifier is not normalized; pure.

**Example.**

```php
NumberSequenceScope::Site->key('north');           // '-'
NumberSequenceScope::Organization->key('north');   // 'north'
NumberSequenceScope::Organization->key(null);      // throws InvalidArgumentException
```

## `Kumwe\Sequence\Contract\NumberSequenceAllocator`

| | |
| --- | --- |
| Kind | interface; extension point — the host implements it once per store technology |
| Stability | stable since 0.1.0 |
| File | `src/Contract/NumberSequenceAllocator.php` |
| Capability | `sequence.allocation` |
| State | none declared; an adapter keeps per-transaction state in its store and holds none itself |
| Concurrency | an adapter is bound to one connection and is not shared across threads or processes |

**Responsibility.** Reserve the next value of one counter inside the caller's own transaction. A document
number is not a deduplicated string: a run has to be contiguous, which a unique index can only refuse to
break, never produce. The port is the primitive that produces it.

**Invariants an adapter owes — the gapless-on-commit promise.**

1. Within one counter — the five coordinates together — the values handed to committed records are
   contiguous from one, with no duplicates and no gaps.
2. A command that rolls back for any reason, including a later failure in the same transaction, consumes
   nothing: the allocation joins the caller's transaction and never opens its own.
3. A replayed idempotent command allocates nothing; it returns the number the original command stored.
4. Numbers are never re-used; a hole left by a hard-deleted record or an operator's edit is never filled.
5. The adapter holds the counter exclusively until the enclosing transaction ends, so concurrent commands
   against one counter run one at a time.
6. When the counter cannot be reserved now, the adapter reserves nothing and raises
   `NumberSequenceUnavailable`, translating its driver's own contention class and keeping it as the previous
   exception.

### `allocate(string $siteIdentifier, string $definitionId, string $fieldHandle, string $scopeKey, string $periodKey, DateTimeImmutable $now): int`

**Responsibility.** Reserve the next value of the counter the five coordinates name.

**Parameters.** All required, none nullable.

- `$siteIdentifier` — `string`. The site (legal entity) the numbered record belongs to, or the definition's
  own immutable catalog site when the record scope carries no site dimension.
- `$definitionId` — `string`. Identifier of the definition declaring the numbered field; with the field
  handle it is the document type the counter belongs to.
- `$fieldHandle` — `string`. Handle of the allocated-number field being filled.
- `$scopeKey` — `string`. The tenancy key from `NumberSequenceFormat::counter()` (`-` or an organization).
- `$periodKey` — `string`. The period key from that same call, or the host's declared posting-period key
  for a fiscal reset; empty for a lifetime run.
- `$now` — `DateTimeImmutable`. The instant the allocation is made at; an adapter may stamp it on the
  counter. It is the host's server clock, never a client-asserted instant.

**Returns.** `int` — the reserved value, exactly one higher than the last committed allocation of this
counter; one or more.

**Exceptions.** `NumberSequenceUnavailable` when another allocator holds the counter, created it first in a
first-use race, timed out or deadlocked with this one, or won a compare-and-set the adapter asserts. In
every case nothing was reserved and the caller replays the whole command. An adapter surfaces a genuine
fault (a corrupt counter, a missing transaction) in its own classification; those are defects, not
refusals, and this package declares no type for them.

**Side effects and state mutation.** Advances the counter by one inside the caller's transaction; the
advance commits or rolls back with the command. Holds the counter until the transaction ends.

**Nullability.** Never null; every argument is required.

**Precision and canonicalization.** The coordinates are compared exactly as strings; the adapter does not
normalize them. The value fits a signed 64-bit integer.

**Transaction expectations.** The caller must already hold an open transaction; an adapter must refuse to
allocate outside one, because a reservation that survived a rolled-back command would tear a hole in the
run. The caller renders and stores the number in the same transaction.

**Concurrency and process safety.** One adapter instance serves one connection. Two commands against one
counter serialize on the adapter's hold; commands against different counters do not contend.

**Example.**

```php
$counter = $format->counter($scope->organizationIdentifier, $now);
$value = $this->numbers->allocate($siteIdentifier, $definition->id, $field->handle, $counter['scope'], $counter['period'], $now);
$number = $format->render($value, $counter['period']);   // stored on the record in the same transaction
```

## `Kumwe\Sequence\Exception\NumberSequenceUnavailable`

| | |
| --- | --- |
| Kind | final class extending `RuntimeException`; not an extension point |
| Stability | stable since 0.1.0 |
| File | `src/Exception/NumberSequenceUnavailable.php` |
| Capability | `sequence.allocation` |
| State | the fixed message, code 0 and the optional chained cause |
| Concurrency | not applicable |

**Responsibility.** The one refusal an allocation answers with: the counter cannot be reserved now, nothing
was consumed, the run is intact, replay the command rather than guess at a value. One canonical type lets a
host's retry policy catch exactly it, and keeps a driver's own classification from leaking through the port.
It is never raised for a malformed declaration or an unrenderable value; those are `InvalidArgumentException`
from the value types.

### `__construct(?Throwable $previous = null)`

**Responsibility.** Build the refusal, chaining the failure it stands in for when there is one.

**Parameters.**

- `$previous` — `?Throwable`, optional, default `null`. The driver or infrastructure failure the adapter
  translated, kept for the log; `null` when the condition was detected directly (a compare-and-set that
  affected no row, say).

**Returns.** Not applicable.

**Exceptions.** None. **Side effects.** None.

**Message.** Always `The number sequence counter is temporarily unavailable; replay the allocation.`; the
code is always `0`. The identity is deterministic so a log line and a retry policy see one thing.

**Example.**

```php
try {
    $value = $this->locked($coordinates);
} catch (DriverContention $contention) {
    throw new NumberSequenceUnavailable($contention);   // the adapter's translation at the port boundary
}
```
