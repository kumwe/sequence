# Core contract: values and allocation in a host

This document is for the host that owns a store — Kumwe App first — and for any package that types against
the numbering vocabulary. It says how to install the package, what the host validates at publication, how a
record command composes the values with the port, what an adapter owes, how to bind it, how to translate the
refusal into the host's retry policy, and how to test. It never asks the host to copy anything from this
package.

## 1. Install and pin

```bash
composer require kumwe/sequence:0.2.1
```

While the package is pre-1.0 the host pins an exact version, never a range, and a re-pin is a deliberate,
reviewed change with its own evidence. There is no `ConfigProvider` to register and nothing to configure.

## 2. Validate the declaration at publication (host)

A numbered field's configuration is read with `NumberSequenceFormat::fromConfiguration()`; an
`InvalidArgumentException` is the package saying the declaration is outside the grammar, and the host's
definition validator turns it into its own publication refusal so the first record create never discovers
it. The host owns the rules that need its own vocabulary and keeps them beside the package's:

| Rule | Owner | Why |
| --- | --- | --- |
| grammar, bounds, defaults of `scope`, `reset`, `prefix`, `padding`, `timezone` | package | the number's meaning |
| the column is at least `NumberSequenceFormat::MAXIMUM_LENGTH` wide | host | no rendered number can outgrow it |
| `organization` scope only where the entity's scope mode carries an organization | host | the counter needs a key |
| `fiscal-period` reset only beside a declared posting-date field | host | the period key comes from the posting date |

The host also requires the numbered field itself to be server-only, read-only, immutable after create,
undefaulted, required, unique and not computed, because the allocator fills it and nothing else may. Kumwe
App keeps every host rule in `BusinessDefinitionValidator::validateSequence()` and its neighbours; they read
the package constants and cases and stay in the App.

## 3. Compose the values with the port in a record command (host)

```php
use Kumwe\Sequence\Contract\NumberSequenceAllocator;
use Kumwe\Sequence\Exception\NumberSequenceUnavailable;
use Kumwe\Sequence\Value\NumberSequenceFormat;
use Kumwe\Sequence\Value\NumberSequenceReset;

$format = NumberSequenceFormat::fromConfiguration($field->configuration);
$counter = $format->reset === NumberSequenceReset::FiscalPeriod
    ? ['scope' => $format->scope->key($organization), 'period' => $this->fiscalPeriodKey($postingDate)]
    : $format->counter($organization, $now);
$value = $this->numbers->allocate($site, $definitionId, $field->handle, $counter['scope'], $counter['period'], $now);
$number = $format->render($value, $counter['period']);
```

Inside the command's own transaction, after the record is authorized and validated and before it is
written, so the number, the row and the audit entry commit together or not at all. For a `fiscal-period`
reset the host composes the scope key itself and resolves the period key from the record's declared posting
date through its own posting-period calendar; `counter()` refuses to compose one from the instant, on
purpose.

## 4. Implement the adapter (host only)

The host implements the port once per store technology. An adapter must honour, and the host's integration
tests must prove on every engine the host supports:

| Promise | What the host proves |
| --- | --- |
| Committed records run contiguously from one per counter | interleaved creates on one counter, then a gap-free run |
| A rolled-back command consumes nothing | a refused create, then the next create gets the returned number |
| A replayed idempotent command allocates nothing | a replay returns the stored number and the counter stands still |
| The allocation joins the caller's transaction, never its own | allocation outside a transaction is refused |
| The counter is held exclusively until the transaction ends | a second connection waits or times out into a refusal |
| Every coordinate isolates its own run | site, definition, field, scope and period neighbours each start at one |
| Contention is one refusal at the port | lock wait, deadlock, first-use race, lost compare-and-set: each raises it |
| The driver's own class stays reachable | `getPrevious()` of the refusal is the translated driver failure |

Kumwe App's `DoctrineBusinessNumberSequenceAllocator` takes the counter row `FOR UPDATE` and advances it with
a compare-and-set that must affect exactly one row, seeds the row on first use and lets the unique index
settle a first-use race, and classifies lock waits, deadlocks and the PostgreSQL `55P03` lock timeout as
contention. It stays in the App with its migration and its database evidence on MariaDB, MySQL and
PostgreSQL. A second host writes its own adapter against the same table.

## 5. Bind the adapter

The port FQCN is the service identifier. Bind the host's adapter to it; never alias a historical name, never
register a fallback, never register an in-memory allocator.

Kumwe App's composition root:

```php
$container->share(NumberSequenceAllocator::class, static fn (Container $c): NumberSequenceAllocator =>
    new DoctrineBusinessNumberSequenceAllocator(
        self::service($c, Connection::class),
        self::service($c, TableNames::class),
    ), true);
```

A Laminas ServiceManager host:

```php
'factories' => [
    DoctrineBusinessNumberSequenceAllocator::class => DoctrineBusinessNumberSequenceAllocatorFactory::class,
],
'aliases' => [
    NumberSequenceAllocator::class => DoctrineBusinessNumberSequenceAllocator::class,
],
```

Lifetime: the adapter is bound to one connection whose open transaction every allocation joins, so it lives
exactly as long as that connection — shared per request or per worker, never across threads or processes,
never as a global.

## 6. Translate the refusal into the host's retry policy

The port raises exactly one refusal. A host that already has a retryable record exception catches the
package refusal at the port boundary and translates it, keeping the package type out of the rest of its
application layer:

```php
try {
    $value = $this->numbers->allocate(...);
} catch (NumberSequenceUnavailable $refusal) {
    throw new BusinessRecordTemporarilyUnavailable($refusal);   // the App's retryable record exception
}
```

The host decides how many replays it makes and when to give up; the package only promises that nothing was
consumed. A host that has no such policy lets the refusal reach its delivery layer, where it is a temporary
condition, never a validation failure.

## 7. Test against the port

For a unit test of a service that only needs the port to exist, write an in-memory allocator against the
contract in the host's own test support — the reference in `examples/allocate-and-render.php` is the shape.
This package deliberately ships none in `src/`: an allocator that exists in production code invites a
fallback.

For the adapter itself, write integration tests against a real database — the table in section 4 is the
checklist — and keep them in the host; this package cannot prove durability, isolation or contention and
does not try to.

## 8. What not to do

- Do not keep a copy of any value or the port under a host namespace, and do not alias the host's old names
  to these: nothing resolves a retired name.
- Do not widen the grammar in the host — no extra prefix characters, no thirteenth digit, no new reset —
  by parsing the configuration yourself; the grammar lives here and a change to it is a new release.
- Do not catch the port's refusal and guess at a number, and do not let a driver exception cross the port.
- Do not implement retry, locking or fencing policy here or ask this package to; those are host decisions
  around the port, not the port.
