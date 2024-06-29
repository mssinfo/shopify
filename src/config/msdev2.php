<?php
return [
    "shopify_api_key"=>env('SHOPIFY_API_KEY', '63f2fa001dd7228268d7c5f920f9b28b'),
    "shopify_api_secret"=>env('SHOPIFY_API_SECRET', '47f72686a3950d8f9bf307f5eea1f071'),
    "scopes"=>env('SHOPIFY_API_SCOPES', 'read_content,read_files,write_files,read_themes,write_themes,read_metaobjects,write_metaobjects,read_script_tags,read_script_tags,read_themes'),
    "app_id"=>env('SHOPIFY_APP_ID', 'msdev2'),
    "api_version"=>env('SHOPIFY_API_VERSION', '2023-04'),
    "webhooks"=>env('SHOPIFY_WEBHOOKS', 'APP_UNINSTALLED,THEMES_PUBLISH,SHOP_UPDATE'),
    'appbridge_enabled' => (bool) env('SHOPIFY_APPBRIDGE_ENABLED', true),
    "appbridge_version"=>env('SHOPIFY_APPBRIDGE_VERSION', '3'),
    "is_embedded_app"=> (bool) env('SHOPIFY_IS_EMBEDDED_APP',true) ?? true,
    "enable_alpinejs"=> (bool) env('SHOPIFY_ENABLE_ALPINEJS',true) ?? true,
    "enable_turbolinks"=> (bool) env('SHOPIFY_ENABLE_TURBOLINKS',true) ?? true,
    "tawk_url"=> env('TAWK_URL',''),
    "footer"=>env('SHOPIFY_FOOTER', '<p>Copyright &copy; All right reserved.</p>'),
    "test_stores"=>env('SHOPIFY_TEST_STORES',''),
    "shopify_app_url"=>env('SHOPIFY_APP_URL',''),
    'contact_url'=>env('SHOPIFY_CONTACT_URL',''),
    'contact_email'=>env('SHOPIFY_CONTACT_EMAIL',''),
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
                'position'=>'all',//sidebar,topbar*,all,
                'type'=>'web' //vue,web*,laravel
            ],
            [
                'label'=> 'Setting',
                'destination'=> 'setting',
                'position'=>'all',//sidebar,topbar*,all,
                'icon'=>'<i class="icon-gear"></i>'
            ],
            [
                'label'=> 'Support',
                'destination'=> 'help',
                'position'=>'all',//sidebar,topbar*,all,
                'icon'=>'<i class="icon-users"></i>'
            ]
        ]
    ],
    "billing" => env('SHOPIFY_BILLING', true),
    "plan_offer"=>[
        "enable"=>false,
        "heading"=>"Annual Plan Offer",
        "detail"=>"<p align='left'>Maximize your savings with the Annual Plan. Enjoy<strong> up to 17% </strong>off. Sign up now!</p>",
        "yearly"=>[
            "info"=>"save 16%+",
            "offer"=>"get_2_months_free"
        ]
    ],
    "plan"=>[
        [
            'chargeName'=>'FREE',
            'interval'=>'EVERY_30_DAYS',//EVERY_30_DAYS|ANNUAL|ONE_TIME
            'amount'=>0,
            'currencyCode'=>'USD',
            'credit'=>100,
            'trialDays'=>0,
            'properties'=>[ // for plan desigining
                [
                    'name'=>'Rain on page',
                    'help_text'=>'', //optional
                    'value'=>'true', //true=check,false=cross
                ],
                [
                    'name'=>'Add Url',
                    'help_text'=>'Add spaecific url where you want to show rain',
                    'value'=>'false', //true=check,false=cross,string
                ],
                [
                    'name'=>'Set Timing',
                    'help_text'=>'Set timing for rain in and out on page',
                    'value'=>'false', //true=check,false=cross,string
                ],
                [
                    'name'=>'Change rain speed',
                    'help_text'=>'Set speed of rain',
                    'value'=>'false', //true=check,false=cross,string
                ],
                [
                    'name'=>'Change rain image',
                    'help_text'=>'Set Image for rain',
                    'value'=>'false', //true=check,false=cross,string
                ],
                [
                    'name'=>'Email Support',
                    'value'=>'true',
                ]
            ],
            'feature'=>[ // for develoepr
                'plan'=>'all'
            ]
        ],
        [
            'chargeName'=>'PRO',
            'interval'=>'EVERY_30_DAYS',//EVERY_30_DAYS|ANNUAL|ONE_TIME
            'amount'=>1.5,
            'currencyCode'=>'USD',
            'credit'=>1000,
            'trialDays'=>7,
            'properties'=>[
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
                    'name'=>'Set Timing',
                    'help_text'=>'Set timing for rain in and out on page',
                    'value'=>'true', //true=check,false=cross,string
                ],
                [
                    'name'=>'Change rain speed',
                    'help_text'=>'Set speed of rain',
                    'value'=>'true', //true=check,false=cross,string
                ],
                [
                    'name'=>'Change rain image',
                    'help_text'=>'Set Image for rain',
                    'value'=>'true', //true=check,false=cross,string
                ],
                [
                    'name'=>'Email Support',
                    'value'=>'true',
                ]
            ],
            'feature'=>[ // for develoepr
                'plan'=>'all'
            ]
        ]
    ]
];
