<?php

namespace App\Services\Tenant;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Events\CreateOrganizationProcessed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class OrganizationService
{

    public function create($data)
    {
        $this->validate($data);

        $organization = null;

        DB::transaction(function () use ($data, &$organization) {
            $user = Auth()->user();

            $organization = $user->organizations()->create([
                'razao_social' => $data['razao_social'],
                'cnpj' => $data['cnpj'],
            ]);

            $organization->digitalCertificate()->create($data);

            $user->last_organization_id = $organization->id;
            $user->saveQuietly();

            Cache::forget('all_valid_organizations_for_user_' . $user->id);

            event(new CreateOrganizationProcessed($user, $organization));
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

    public function checkOwnerCertificate($organization, $data)
    {

        if ($organization->cnpj != $data['cnpj']) {
            throw new Exception('O certificado informado não pertence a esta organização');
        }
    }

    protected function validate(array $data, string $context = 'create', Organization $organization = null): void
    {
        $rules = [
            'cnpj' => 'required|string|max:50|unique:organizations,cnpj',
        ];

        if ($context === 'update' && $organization) {
            // rules for update
        }

        $messages = [
            'cnpj.required' => 'O CNPJ é obrigatório.',
            'cnpj.unique' => 'Essa organização já está cadastrada.',
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
