<?php

namespace App\Dto;

final class FirebaseServiceAccount
{
    public function __construct(
        public readonly string $type,
        public readonly string $project_id,
        public readonly string $private_key_id,
        public readonly string $private_key,
        public readonly string $client_email,
        public readonly string $client_id,
        public readonly string $auth_uri,
        public readonly string $token_uri,
        public readonly string $auth_provider_x509_cert_url,
        public readonly string $client_x509_cert_url,
        public readonly string $universe_domain,
    ) {}
}
