<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        td.label {
            width: 33.33%;
            border: 1px dashed #999;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }
        .barcode-img { width: 140px; height: auto; }
        .product-name { margin-top: 4px; font-weight: bold; }
        .product-name-ar { direction: rtl; unicode-bidi: embed; }
        .product-sku { color: #555; }
    </style>
</head>
<body>
    <table>
        @foreach ($products->chunk(3) as $row)
            <tr>
                @foreach ($row as $product)
                    <td class="label">
                        <img class="barcode-img" src="{{ $barcodes[$product->id] }}" alt="{{ $product->barcode }}">
                        <div class="product-name" dir="ltr">{{ $product->name_en }}</div>
                        <div class="product-name product-name-ar" dir="rtl">{{ $product->name_ar }}</div>
                        <div class="product-sku">{{ $product->sku }}</div>
                    </td>
                @endforeach
                @for ($i = $row->count(); $i < 3; $i++)
                    <td class="label"></td>
                @endfor
            </tr>
        @endforeach
    </table>
</body>
</html>
