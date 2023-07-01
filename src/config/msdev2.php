<?php
return [
    "shopify_api_key"=>env('SHOPIFY_API_KEY', '63f2fa001dd7228268d7c5f920f9b28b'),
    "shopify_api_secret"=>env('SHOPIFY_API_SECRET', '47f72686a3950d8f9bf307f5eea1f071'),
    "scopes"=>env('SHOPIFY_API_SCOPES', 'read_content,read_files,write_files,read_metaobjects,write_metaobjects,read_script_tags,read_script_tags,read_themes'),
    "webhooks"=>env('SHOPIFY_WEBHOOKS', 'APP_UNINSTALLED,THEMES_PUBLISH,SHOP_UPDATE'),
    'appbridge_enabled' => (bool) env('SHOPIFY_APPBRIDGE_ENABLED', true),
    "appbridge_version"=>env('SHOPIFY_APPBRIDGE_VERSION', '3'),
    "is_embedded_app"=> (bool) env('SHOPIFY_IS_EMBEDDED_APP',true) ?? true,
    "footer"=>env('SHOPIFY_FOOTER', '<p>Copyright &copy; All right reserved.</p>'),
    "test_stores"=>env('SHOPIFY_TEST_STORES',''),
    "menu"=>[
        'logo'=>[
            'type'=>'url',//image,url,
            'value'=>'https://cdn-icons-png.flaticon.com/512/7190/7190597.png'
        ],
        'list'=>[
            [
                'label'=> 'Dashboard',
                'destination'=> '/',
                'icon'=>'<i class="icon-home"></i>', //optional
                'position'=>'topbar',//sidebar,topbar*,all,
                'type'=>'web' //vue,web*,laravel
            ],
            [
                'label'=> 'Setting',
                'destination'=> 'setting',
                'icon'=>'<i class="icon-gear"></i>'
            ]
        ]
    ],
    "billing" => env('SHOPIFY_BILLING', true),
    "plan"=>[
        [
            'chargeName'=>'LITE',
            'interval'=>'EVERY_30_DAYS',//EVERY_30_DAYS|ANNUAL|ONE_TIME
            'amount'=>1.5,
            'currencyCode'=>'USD',
            'credit'=>100,
            'trialDays'=>7,
            'feature'=>[
                [
                    'name'=>'Rain on page',
                    'help_text'=>'', //optional
                    'value'=>'', //true=check,false=cross
                ],
                [
                    'name'=>'Add Url',
                    'help_text'=>'Add spaecific url where you want to show rain',
                    'value'=>'true', //true=check,false=cross,string
                ],
                [
                    'name'=>'Email Support',
                    'value'=>'true',
                ]
            ]
                ],
        [
            'chargeName'=>'PRO',
            'interval'=>'EVERY_30_DAYS',//EVERY_30_DAYS|ANNUAL|ONE_TIME
            'amount'=>5.5,
            'currencyCode'=>'USD',
            'credit'=>1000,
            'trialDays'=>7,
            'feature'=>[
                [
                    'name'=>'Rain on page',
                    'help_text'=>'', //optional
                    'value'=>'true', //true=check,false=cross
                ],
                [
                    'name'=>'Add Url',
                    'help_text'=>'Add spaecific url where you want to show rain',
                    'value'=>'true', //true=check,false=cross,string
                ],
                [
                    'name'=>'Email Support',
                    'value'=>'true',
                ]
            ]
        ]
    ]
];
