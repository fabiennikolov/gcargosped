<?php

return [

    'testing' => [

        /*
         * The starter kit keeps page components in a lowercase `pages`
         * directory, while Inertia's default only looks in `Pages`. Without
         * this, assertInertia() reports every component as missing.
         */
        'ensure_pages_exist' => true,

        'page_paths' => [
            resource_path('js/pages'),
        ],

        'page_extensions' => [
            'js',
            'jsx',
            'ts',
            'tsx',
            'vue',
        ],

    ],

];
