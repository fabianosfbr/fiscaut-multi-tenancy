<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Declaração de Faturamento</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 40px;
        }
        h1 {
            text-align: center;
            text-decoration: underline;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .total {
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .declaracao-texto {
            margin-bottom: 30px;
            text-align: justify;
        }
        .assinatura {
            margin-top: 50px;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>DECLARAÇÃO DE FATURAMENTO</h1>

    <div class="declaracao-texto">
        Declaro para os devidos fins e efeitos que a empresa: {{ $organization->razao_social }}, 
        estabelecida à {{ $organization->logradouro }}, {{ $organization->numero }} - {{ $organization->bairro }} - {{ $organization->cidade }}/{{ $organization->uf }} - 
        inscrita no CNPJ (MF) nº {{ formatar_cnpj_cpf($organization->cnpj) }}, teve como faturamento no período de: 
        {{ $periodoInicial }} à {{ $periodoFinal }}.
    </div>

    <table>
        <thead>
            <tr>
                <th>Mês</th>
                <th>Ano</th>
                <th class="text-right">Faturamento</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dados as $item)
            <tr>
                <td>{{ $item->mes_nome }}</td>
                <td>{{ $item->ano }}</td>
                <td class="text-right">{{ formatar_moeda($item->valor_total) }}</td>
            </tr>
            @endforeach
            <tr class="total">
                <td colspan="2">Total</td>
                <td class="text-right">{{ formatar_moeda($total) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="assinatura">
        <p>Por ser verdade, firmo a presente declaração.</p>
    </div>
</body>
</html> 