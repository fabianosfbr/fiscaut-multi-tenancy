<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Resumo de Etiquetas</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            padding: 20px;
        }
        h3 {
            margin: 0 auto; 
            width: 75%; 
            padding: 10px; 
            text-align: center;
        }
        .table-area {
            width: 100%;
        }
        table {
            width: 75%;
            margin: 0 auto;
            text-align: center;
            padding-top: 5px;
            border-collapse: collapse;
        }
        th, td {
            padding: 5px;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .codigo {
            text-align: center;
        }
        .etiqueta {
            text-align: center;
        }
        .valor {
            text-align: right;
            padding-right: 5px;
        }
    </style>
</head>
<body>
    <h3>Etiquetas</h3>
    <div class="table-area">
        <table cellspacing="1">
            <thead>
                <tr>
                    <th scope="col" class="codigo">Código</th>
                    <th scope="col" class="etiqueta">Etiqueta</th>
                    <th scope="col" class="valor">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tags as $index => $tagged)
                <tr>
                    <td class="codigo">{{ $tagged['tag']['code'] }}</td>
                    <td class="etiqueta">{{ $tagged['tag']['name'] }}</td>
                    <td class="valor">{{ isset($tagged['value']) ? formatar_moeda($tagged['value']) : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</body>
</html>
