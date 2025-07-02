<?php

namespace App\Factory;

use App\Dto\FirebaseServiceAccount;

final class FirebaseServiceAccountFactory
{
    public static function fromJsonFile(string $path): FirebaseServiceAccount
    {
        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return new FirebaseServiceAccount(
            $data['type'],
            $data['project_id'],
            $data['private_key_id'],
            $data['private_key'],
            $data['client_email'],
            $data['client_id'],
            $data['auth_uri'],
            $data['token_uri'],
            $data['auth_provider_x509_cert_url'],
            $data['client_x509_cert_url'],
            $data['universe_domain'],
        );
    }
}
