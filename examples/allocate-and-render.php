<?php

/**
 * Declare a number format, reserve values through the allocator port, and render the numbers a person reads.
 *
 * The script composes the package exactly as a host's record command does — declaration in, counter
 * coordinates out, reserved value in, rendered number out — against an in-memory reference allocator that
 * lives only in this file. It needs no `composer install`: it loads the package from `vendor/autoload.php`
 * when that exists (the installed archive) and from `src/` otherwise (a checkout).
 *
 * @since 0.1.0
 */

declare(strict_types=1);

namespace Kumwe\Sequence\Examples;

use DateTimeImmutable;
use Kumwe\Sequence\Contract\NumberSequenceAllocator;
use Kumwe\Sequence\Exception\NumberSequenceUnavailable;
use Kumwe\Sequence\Value\NumberSequenceFormat;

$root = dirname(__DIR__);
/** @var list<string> $arguments */
$arguments = $_SERVER['argv'] ?? [];
$autoload = $arguments[1] ?? $root . '/vendor/autoload.php';
if (isset($arguments[1]) && !is_file($autoload)) {
    fwrite(STDERR, "The specified consumer autoloader is missing.\n");
    exit(1);
}
if (is_file($autoload)) {
    require $autoload;
} else {
    spl_autoload_register(static function (string $class) use ($root): void {
        $prefix = 'Kumwe\\Sequence\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $path = $root . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require $path;
        }
    });
}

/**
 * In-memory counters for the demonstration: contiguous per counter, refusing while a counter is held.
 *
 * This is what a host adapter promises, minus everything a store provides — durability, the transaction the
 * allocation joins, the lock that serializes concurrent allocators. It is a demonstration, never a
 * production allocator.
 *
 * @since  0.1.0
 */
final class InMemoryNumberSequenceAllocator implements NumberSequenceAllocator
{
    /**
     * Last value handed out per counter, keyed by the five joined coordinates.
     *
     * @var    array<string, int>
     * @since  0.1.0
     */
    private array $counters = [];

    /**
     * Counters another transaction currently holds, keyed the same way.
     *
     * @var    array<string, true>
     * @since  0.1.0
     */
    private array $held = [];

    /**
     * Pretend another transaction holds a counter, so the next allocation must refuse.
     *
     * @param   string  $coordinates  The five coordinates joined with `|`.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function hold(string $coordinates): void
    {
        $this->held[$coordinates] = true;
    }

    /**
     * Let the other transaction go.
     *
     * @param   string  $coordinates  The five coordinates joined with `|`.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function release(string $coordinates): void
    {
        unset($this->held[$coordinates]);
    }

    /**
     * Reserve the next value of one counter, or refuse while it is held.
     *
     * @param   string             $siteIdentifier  Site the numbered record belongs to.
     * @param   string             $definitionId    Definition declaring the numbered field.
     * @param   string             $fieldHandle     Handle of the numbered field.
     * @param   string             $scopeKey        Tenancy key from the format.
     * @param   string             $periodKey       Period key from the format.
     * @param   DateTimeImmutable  $now             Allocation instant; unused by an in-memory counter.
     *
     * @return  int  The reserved value, contiguous from one per counter.
     *
     * @throws  NumberSequenceUnavailable  While another transaction holds the counter.
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
    ): int {
        $coordinates = implode('|', [$siteIdentifier, $definitionId, $fieldHandle, $scopeKey, $periodKey]);
        if (isset($this->held[$coordinates])) {
            throw new NumberSequenceUnavailable();
        }
        $this->counters[$coordinates] = ($this->counters[$coordinates] ?? 0) + 1;

        return $this->counters[$coordinates];
    }
}

$allocator = new InMemoryNumberSequenceAllocator();

/**
 * Issue one number the way a record command does: coordinates, reservation, rendering.
 *
 * @param   NumberSequenceFormat  $format        The field's declaration.
 * @param   string                $definitionId  Definition (document type) the field belongs to.
 * @param   string                $fieldHandle   Handle of the numbered field.
 * @param   ?string               $organization  Organization the record is scoped to, or null.
 * @param   DateTimeImmutable     $at            Instant the command runs at.
 *
 * @return  string  The rendered number.
 *
 * @since   0.1.0
 */
$issue = static function (
    NumberSequenceFormat $format,
    string $definitionId,
    string $fieldHandle,
    ?string $organization,
    DateTimeImmutable $at,
) use ($allocator): string {
    $counter = $format->counter($organization, $at);
    $value = $allocator->allocate('default', $definitionId, $fieldHandle, $counter['scope'], $counter['period'], $at);

    return $format->render($value, $counter['period']);
};

// A yearly invoice run judged in Windhoek: the run rolls over at local midnight, two hours before UTC does.
$invoice = NumberSequenceFormat::fromConfiguration([
    'reset' => 'yearly',
    'prefix' => 'INV-',
    'padding' => 6,
    'timezone' => 'Africa/Windhoek',
]);
echo $issue($invoice, 'invoice', 'invoice_number', null, new DateTimeImmutable('2026-12-31T21:30:00+00:00')), "\n";
echo $issue($invoice, 'invoice', 'invoice_number', null, new DateTimeImmutable('2026-12-31T21:45:00+00:00')), "\n";
echo $issue($invoice, 'invoice', 'invoice_number', null, new DateTimeImmutable('2026-12-31T22:30:00+00:00')), "\n";

// A per-organization lifetime run: each branch keeps its own contiguous numbers; the branch is a counter
// coordinate, not part of the rendered number.
$branch = NumberSequenceFormat::fromConfiguration(['scope' => 'organization', 'prefix' => 'BR-', 'padding' => 4]);
$at = new DateTimeImmutable('2026-06-01T08:00:00+00:00');
echo $issue($branch, 'branch_note', 'note_number', 'north', $at), "\n";
echo $issue($branch, 'branch_note', 'note_number', 'south', $at), "\n";
echo $issue($branch, 'branch_note', 'note_number', 'north', $at), "\n";

// The one refusal: a held counter reserves nothing, the caller replays, and the run continues where it stood.
$allocator->hold('default|invoice|invoice_number|-|2027');
try {
    $issue($invoice, 'invoice', 'invoice_number', null, new DateTimeImmutable('2026-12-31T23:00:00+00:00'));
} catch (NumberSequenceUnavailable $refusal) {
    echo 'refused: ', $refusal->getMessage(), "\n";
}
$allocator->release('default|invoice|invoice_number|-|2027');
echo $issue($invoice, 'invoice', 'invoice_number', null, new DateTimeImmutable('2026-12-31T23:00:00+00:00')), "\n";
