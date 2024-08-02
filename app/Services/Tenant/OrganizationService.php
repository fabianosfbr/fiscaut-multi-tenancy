<?php

namespace App\Services\Tenant;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OrganizationService
{

    public function create($data)
    {
        $organization = null;

        DB::transaction(function () use ($data, &$organization) {

            $organization = Auth()->user()->organizations()->create($data);

            $organization->digitalCertificate()->create($data);
        });

        return $organization;
    }

    public function update($organization, $data)
    {
        $organization->update($data);

        return $organization;
    }


    public function readerCertificateFile($data): array
    {
        $pfxContent = Storage::get('certificates/' . $data['certificate']);

        $CertPriv = [];

        if (!openssl_pkcs12_read($pfxContent, $x509certdata, $data['password'])) {
            Log::error('Erro ao ler o certificado');
            throw new Exception('Não foi possiível ler o certificado, verifique o formato do arquivo ou a senha informada');
        } else {

            $CertPriv   = openssl_x509_parse(openssl_x509_read($x509certdata['cert']));

            $dadosCertificado = explode(':', $CertPriv['subject']['CN']);
            $data['razao_social'] = $dadosCertificado[0];
            $data['cnpj'] = $dadosCertificado[1];
            $data['validated_at'] = date('Y-m-d H:i:s', $CertPriv['validTo_time_t']);
            $data['content_file'] = $pfxContent;
        }

        Storage::delete('certificates/' . $data['certificate']);

        return $data;
    }

    public function checkOwnerCertificate($organization, $data){

        if($organization->cnpj != $data['cnpj']){
            throw new Exception('O certificado informado não pertence a esta organização');
        }
    }
}
