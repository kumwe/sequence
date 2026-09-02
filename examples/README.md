# Examples

Runnable, dependency-free demonstrations of the adoption path. Each runs from a git checkout with no
`composer install` and from the installed release archive alike; the suite replays them, and the
clean-consumer gate runs them inside the extracted archive.

## `allocate-and-render.php`

```sh
php examples/allocate-and-render.php
```

Two declarations — a yearly invoice run judged in `Africa/Windhoek` and a per-organization lifetime run —
issued through the port exactly as a host's record command does: the format composes the counter
coordinates, the allocator reserves the next value, the format renders the number. The allocator is an
in-memory reference that lives only in the example. The output is deterministic:

```text
INV-2026-000001
INV-2026-000002
INV-2027-000001
BR-0001
BR-0001
BR-0002
refused: The number sequence counter is temporarily unavailable; replay the allocation.
INV-2027-000002
```

What it shows, line by line:

- `INV-2026-000001`, `INV-2026-000002` — a yearly run, contiguous from one, rendered as prefix, period
  segment, hyphen and zero-padded digits.
- `INV-2027-000001` — the third invoice is raised at 22:30 UTC on 31 December, which is already 00:30 on
  1 January in Windhoek: the reset is judged in the declared zone, so a new run starts while UTC is still
  in the old year.
- `BR-0001`, `BR-0001`, `BR-0002` — a per-organization run: `north` and `south` each keep their own
  contiguous numbers. The organization is a counter coordinate, not part of the rendered number.
- `refused: …` — the counter is held elsewhere, so the allocation answers with the one refusal,
  `NumberSequenceUnavailable`, and reserves nothing.
- `INV-2027-000002` — once the counter is free the run continues exactly where it stood: the refusal
  consumed no number.

**This is a demonstration, never a production wiring.** The in-memory allocator is example support; a host
implements the port over its own store, inside its own transaction, as
[`docs/integration.md`](../docs/integration.md) describes.
