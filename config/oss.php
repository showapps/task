<?php

return [
    'access_id'=>'',
    'access_secret'=>'',
    'endpoint'=>'oss-cn-shenzhen.aliyuncs.com',
    'bucket'=>'',
    'domain_header'=>'',
    'image' =>[
        'max_size'=>10485760,
        'allow_exts'=>["jpg","gif","png","jpeg"],
    ],
    'video' =>[
        'max_size'=>104857600,
        'allow_exts'=>["mp4","3gp","m3u8"],
    ],
    'voice' =>[
        'max_size'=>104857600,
        'allow_exts'=>["mp3"],
    ],
    'excel' =>[
        'max_size'=>104857600,
        'allow_exts'=>["xls","xlsx"],
    ]
];