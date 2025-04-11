<?php

use App\Models\Tenant\ControleNsu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Transfere os dados de NFe para a nova tabela
        $nfeRecords = DB::table('controle_nsu')
            ->where('tipo_documento', 'NFe')
            ->get();

        foreach ($nfeRecords as $record) {
            DB::table('controle_nsu_nfe')->insert([
                'id' => Str::uuid()->toString(),
                'organization_id' => $record->organization_id,
                'ultimo_nsu' => $record->ultimo_nsu,
                'max_nsu' => $record->max_nsu,
                'ultima_consulta' => $record->ultima_consulta,
                'xml_content' => $record->xml_content,
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
            ]);
        }

        // Transfere os dados de CTe para a nova tabela
        $cteRecords = DB::table('controle_nsu')
            ->where('tipo_documento', 'CTe')
            ->get();

        foreach ($cteRecords as $record) {
            DB::table('controle_nsu_cte')->insert([
                'id' => Str::uuid()->toString(),
                'organization_id' => $record->organization_id,
                'ultimo_nsu' => $record->ultimo_nsu,
                'max_nsu' => $record->max_nsu,
                'ultima_consulta' => $record->ultima_consulta,
                'xml_content' => $record->xml_content,
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        // A migração reversa não é necessária, pois os dados já estariam na tabela original
    }
}; 