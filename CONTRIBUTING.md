# Contributing

This repository is a runnable demo that accompanies the `k2gl/sigstore-verify` article
series. It is an example project, not a published library — there is no build gate or
test suite.

## Running the demo

```bash
composer install
```

Then run the examples, for instance:

```bash
php verify.php       # verify a real signed release, offline
php negative.php     # see verification fail on tampered input
```

See the [README](README.md) for the full walkthrough.

## Proposing changes

- Keep the examples runnable and self-contained.
- Create a branch off `main` and open a pull request describing what changes and why.
- Use clear commit messages ([Conventional Commits](https://www.conventionalcommits.org/)
  are appreciated).

## Reporting issues and suggestions

Use the issue templates (Bug report / Feature request). For security issues, please
follow the [security policy](SECURITY.md) rather than opening a public issue.
