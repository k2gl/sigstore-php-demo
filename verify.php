<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use K2gl\InToto\Statement;
use K2gl\Sigstore\Bundle;
use K2gl\Sigstore\IdentityPolicy;
use K2gl\Sigstore\SigstoreVerifier;
use K2gl\Sigstore\SubjectPolicy;
use K2gl\Sigstore\TrustedRoot;
use K2gl\Slsa\Provenance;

$usage = 'Usage: php verify.php <artifact> <attestation.jsonl> [owner/repo] [ref] [trusted_root.json]';
$artifact = $argv[1] ?? throw new RuntimeException($usage);
$attestationPath = $argv[2] ?? throw new RuntimeException($usage);
$repository = $argv[3] ?? 'k2gl/sigstore-verify';
$ref = $argv[4] ?? 'refs/heads/main';
$trustedRootPath = $argv[5] ?? null;

// `gh attestation download` writes JSON Lines: one Sigstore bundle per line.
$lines = file($attestationPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$bundle = Bundle::fromJson($lines[0]);

// A local trusted root makes the run fully offline; without one, fetch the
// Sigstore public-good root via TUF — the only network call here.
$trustedRoot = $trustedRootPath !== null
    ? TrustedRoot::fromJson((string) file_get_contents($trustedRootPath))
    : TrustedRoot::fromSigstorePublicGood();

// Who must have signed: this repository's attest.yml workflow on this ref.
$identity = IdentityPolicy::githubActions(
    repository: $repository,
    workflow: 'attest.yml',
    ref: $ref,
);

// What must have been signed: this exact file.
$subject = new SubjectPolicy('sha256', hash_file('sha256', $artifact));

$envelope = (new SigstoreVerifier())->verify(
    bundle: $bundle,
    trustedRoot: $trustedRoot,
    identityPolicy: $identity,
    subjectPolicy: $subject,
);

// The payload is authenticated now — model it with the typed packages.
$statement = Statement::fromEnvelope($envelope);
$provenance = Provenance::fromStatement($statement);

echo "VERIFIED\n";
echo 'subject:   ' . $statement->subject[0]->name . ' (sha256:' . $statement->subject[0]->digest['sha256'] . ")\n";
echo 'builder:   ' . $provenance->runDetails->builder->id . "\n";
echo 'commit:    ' . $provenance->buildDefinition->resolvedDependencies[0]->digest['gitCommit'] . "\n";
