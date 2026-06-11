# sigstore-php-demo

Runnable demo: verify [Sigstore](https://www.sigstore.dev/) attestations of real signed
releases in pure PHP, offline, in about five minutes. Companion repository for the
[k2gl/sigstore-verify](https://github.com/k2gl/sigstore-verify) article series.

The `examples/` directory contains a real release artifact of
[k2gl/dsse](https://github.com/k2gl/dsse) together with its attestation bundle, exactly
as published by GitHub Artifact Attestations: a tarball built by a GitHub Actions
workflow and signed keyless through the Sigstore public-good instance (Fulcio
certificate, DSSE envelope with SLSA provenance, Rekor transparency-log proof).

## Quick start

```bash
git clone https://github.com/k2gl/sigstore-php-demo.git
cd sigstore-php-demo
composer install
```

Verify the example artifact with the CLI — fully offline, against the bundled
trusted-root snapshot:

```bash
vendor/bin/sigstore-verify examples/dsse-1.1.1.tar.gz examples/dsse-1.1.1.tar.gz.sigstore.jsonl \
  --repository k2gl/dsse --workflow attest.yml --ref refs/tags/1.1.1 \
  --trusted-root trusted_root.json
```

Or from PHP code, with typed SLSA provenance on top:

```bash
php verify.php examples/dsse-1.1.1.tar.gz examples/dsse-1.1.1.tar.gz.sigstore.jsonl \
  k2gl/dsse refs/tags/1.1.1 trusted_root.json
```

Expected output:

```
VERIFIED
subject:   dsse-1.1.1.tar.gz (sha256:7a719ac27ce8c64af4992222213dcbfc240d412719e0b5e6107392f4e6c9f7ba)
builder:   https://github.com/k2gl/dsse/.github/workflows/attest.yml@refs/tags/1.1.1
commit:    d9716be40f51e2bc32f6328a4f1830dd12156a45
```

## Watch it fail closed

`negative.php` runs two attacks against the same bundle — a tampered artifact and a
forged signer identity — and expects both to be rejected:

```bash
php negative.php examples/dsse-1.1.1.tar.gz examples/dsse-1.1.1.tar.gz.sigstore.jsonl \
  k2gl/dsse refs/tags/1.1.1 trusted_root.json
```

## Verify any other release

Any public repository that uses
[GitHub Artifact Attestations](https://docs.github.com/en/actions/security-for-github-actions/using-artifact-attestations)
works the same way. Download an artifact and its bundle, then point the verifier at them:

```bash
gh release download <tag> --repo <owner>/<repo> --pattern '<artifact>'
gh attestation download <artifact> --repo <owner>/<repo>

vendor/bin/sigstore-verify <artifact> sha256:*.jsonl --repository <owner>/<repo>
```

## Files

| File | Purpose |
|---|---|
| `verify.php` | library-level verification + typed SLSA provenance |
| `negative.php` | fail-closed demo: tampered artifact, wrong identity |
| `trusted_root.json` | snapshot of the Sigstore public-good trusted root for offline runs |
| `examples/` | a real signed release: tarball + attestation bundle |

## About the trusted root

`trusted_root.json` is a snapshot: good enough for the demo, but a stale or substituted
trust root silently undermines verification. For real deployments either refresh it
periodically (it is distributed via TUF) or omit the `--trusted-root` option and let the
verifier fetch the current root through its built-in TUF client (one network call).

## License

MIT
