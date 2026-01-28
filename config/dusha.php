<?php

return [
    /*
     * Source directory for your assets (relative to base_path)
     */
    "source_path" => "resources/assets",

    /*
     * Target directory for compiled assets (relative to base_path)
     */
    "output_path" => "assets",

    /*
     * Asset paths to compile
     */
    "paths" => ["css", "js", "images", "fonts"],

    /*
     * File extensions to process
     */
    "extensions" => ["css", "js"],

    /*
     * Length of digest hash (default: 8)
     */
    "digest_length" => 8,

    /*
     * Enable CSS URL rewriting
     */
    "rewrite_css_urls" => true,
];
