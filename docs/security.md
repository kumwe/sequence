# Security policy

## Scope

`kumwe/sequence` is a value and port package. It performs no I/O, opens no connection, reads no input
beyond the declaration map a host hands it, stores nothing and requires no extension and no Composer
dependency. Its attack surface is the declaration it validates and the values it renders: the grammar is
closed (a prefix of at most 16 characters from `A-Z 0-9 - /`, padding 1 to 12, a known timezone, backed
enum values), an unusable declaration is refused rather than defaulted over, and a rendered number can
never exceed 36 characters, so a host that sizes its column to `MAXIMUM_LENGTH` cannot be handed a value
that does not fit. The package never invokes a callable and never selects behaviour at runtime.

The allocation promise — contiguity, nothing consumed on rollback, nothing reserved by a refusal — is a
property of the host's adapter and store, and a host proves it with its own database evidence. The
package's contribution is one canonical refusal type, so an adapter cannot leak a driver's own failure
classification through the port and a retry policy cannot mistake a defect for contention.

## Supply chain

- The runtime requirement is `php` alone; `composer audit --abandoned=fail` runs in CI over the
  development toolchain.
- Every third-party GitHub Action is pinned to a reviewed commit SHA; workflow permissions default to
  read-only and are widened only in the release job that creates the tag and the release.
- Releases are built from the merged `main` commit by automation; no human pushes a tag, and no registry
  credential enters this repository.
- The release archive is held to an exact reviewed file set and installed as a clean consumer before it
  is published.

## Reporting a vulnerability

Report privately through the GitHub security advisory form of the repository
(`https://github.com/kumwe/sequence/security/advisories/new`) rather than in a public issue. Include
the affected version, a description and, where possible, a reproduction. A fix ships as a new version
with a changelog entry naming the affected versions; released versions are never modified.

## What this package cannot promise

Durability, isolation, exclusive holds and correct rollback of a counter are properties of the host's
adapter and store, not of this package. Which site or organization a record belongs to, and whether the
caller may allocate at all, are the host's authority. This package only states what a number means and
what an adapter owes.
