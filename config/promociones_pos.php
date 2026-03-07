<?php

// Reglas de promociones para POS.
// Tipos soportados:
// - 2x1: { "type":"2x1", "label":"2x1 Shampoo", "product_ids":[1] }
// - percent: { "type":"percent", "label":"10% OFF", "product_ids":[2], "percent":10, "min_qty":1 }
// - combo: { "type":"combo", "label":"Combo Bebida+Snack", "combo_price":500, "required_items":[{"product_id":3,"qty":1},{"product_id":4,"qty":1}] }
//
// Tambien se puede usar "codigos_barras": ["7791234567890"] en vez de product_ids.

return [
    'updated_at' => date('Y-m-d H:i:s'),
    'rules' => [
        [
            'type' => '2x1',
            'label' => '2x1 Cuidado Personal',
            'codigos_barras' => [
                '779991000001',
                '779991000011',
                '779991000021'
            ]
        ],
        [
            'type' => 'percent',
            'label' => '15% OFF OTC',
            'codigos_barras' => [
                '779991000003',
                '779991000013',
                '779991000023',
                '779991000033'
            ],
            'percent' => 15,
            'min_qty' => 1
        ],
        [
            'type' => 'combo',
            'label' => 'Combo Bebida + Snack',
            'combo_price' => 1400,
            'required_items' => [
                ['codigo_barras' => '779991000004', 'qty' => 1],
                ['codigo_barras' => '779991000005', 'qty' => 1]
            ]
        ]
    ]
];
