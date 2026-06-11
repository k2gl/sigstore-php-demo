<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use K2gl\Sigstore\Bundle;
use K2gl\Sigstore\Exception\SigstoreException;
use K2gl\Sigstore\IdentityPolicy;
use K2gl\Sigstore\SigstoreVerifier;
use K2gl\Sigstore\SubjectPolicy;
use K2gl\Sigstore\TrustedRoot;

$usage = 'Usage: php negative.php <artifact> <attestation.jsonl> [owner/repo] [ref] [trusted_root.json]';
$artifact = $argv[1] ?? throw new RuntimeException($usage);
$attestationPath = $argv[2] ?? throw new RuntimeException($usage);
$repository = $argv[3] ?? 'k2gl/sigstore-verify';
$ref = $argv[4] ?? 'refs/heads/main';
$trustedRootPath = $argv[5] ?? null;

$lines = file($attestationPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$bundle = Bundle::fromJson($lines[0]);
$trustedRoot = $trustedRootPath !== null
    ? TrustedRoot::fromJson((string) file_get_contents($trustedRootPath))
    : TrustedRoot::fromSigstorePublicGood();
$verifier = new SigstoreVerifier();

$identity = IdentityPolicy::githubActions(
    repository: $repository,
    workflow: 'attest.yml',
    ref: $ref,
);

// Case 1: tampered artifact — one extra byte must fail the subject policy.
$tampered = hash('sha256', file_get_contents($artifact) . 'x');

try {
    $verifier->verify(
        bundle: $bundle,
        trustedRoot: $trustedRoot,
        identityPolicy: $identity,
        subjectPolicy: new SubjectPolicy('sha256', $tampered),
    );

    echo "FAIL: tampered artifact was accepted\n";
    exit(1);
} catch (SigstoreException $e) {
    echo 'OK: tampered artifact rejected — ' . $e->getMessage() . "\n";
}

// Case 2: same bundle, wrong signer identity — must fail the identity policy.
$wrongIdentity = IdentityPolicy::githubActions(
    repository: 'evil' . substr($repository, strpos($repository, "/")),
    workflow: 'attest.yml',
    ref: $ref,
);

try {
    $verifier->verify(
        bundle: $bundle,
        trustedRoot: $trustedRoot,
        identityPolicy: $wrongIdentity,
        subjectPolicy: new SubjectPolicy('sha256', hash_file('sha256', $artifact)),
    );

    echo "FAIL: wrong identity was accepted\n";
    exit(1);
} catch (SigstoreException $e) {
    echo 'OK: wrong identity rejected — ' . $e->getMessage() . "\n";
}

echo "fail-closed works\n";
