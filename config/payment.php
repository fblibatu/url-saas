<?php
return [
    'default_methods' => [
        [
            'name' => 'Payeer',
            'code' => 'payeer',
            'status' => true,
            'min_deposit' => 1.00,
            'min_withdrawal' => 10.00,
            'fee_percent' => 1.00,
            'config' => [
                'account_number' => '',
                'api_id' => '',
                'api_key' => '',
                'merchant_id' => '',
                'encryption_key' => ''
            ]
        ],
        [
            'name' => 'PAPARA',
            'code' => 'papara',
            'status' => true,
            'min_deposit' => 5.00,
            'min_withdrawal' => 20.00,
            'fee_percent' => 0.50,
            'config' => [
                'api_key' => '',
                'account_number' => ''
            ]
        ],
        [
            'name' => 'Bitcoin',
            'code' => 'bitcoin',
            'status' => true,
            'min_deposit' => 0.001,
            'min_withdrawal' => 0.01,
            'fee_percent' => 0.00,
            'config' => [
                'wallet_address' => '',
                'confirmations' => 3
            ]
        ],
        [
            'name' => 'Banka Transferi',
            'code' => 'bank_transfer',
            'status' => true,
            'min_deposit' => 10.00,
            'min_withdrawal' => 50.00,
            'fee_percent' => 0.00,
            'config' => [
                'bank_name' => '',
                'account_name' => '',
                'account_number' => '',
                'iban' => '',
                'branch' => ''
            ]
        ]
    ]
];