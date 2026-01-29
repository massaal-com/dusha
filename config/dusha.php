<?php

return [
    /*
     * Source directory for your assets (relative to base_path)
     */
    "source_path" => "resources/assets",

    /*
     * Target directory for compiled assets (relative to public_path)
     */
    "output_path" => "assets",

    /*
     * Asset paths to compile
     */
    "paths" => ["css", "js", "images", "fonts"],

    /*
     * File extensions to process
     */
    "extensions" => [
        "css",
        "js",
        "jpg",
        "jpeg",
        "png",
        "gif",
        "svg",
        "webp",
        "woff",
        "woff2",
        "ttf",
        "eot",
        "otf",
    ],

    /*
     * Length of digest hash (default: 8)
     */
    "digest_length" => 8,

    /*
     * Rewrite relative URLs in CSS files to use hashed asset paths
     */
    "css_url_rewriting" => true,
];
