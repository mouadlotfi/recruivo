<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Search Synonyms
    |--------------------------------------------------------------------------
    |
    | A term maps to alternative spellings/abbreviations that should match
    | when ranking. Lookups are symmetric via the primary term, so keep each
    | group's primary key listed in its own expansion set.
    |
    */

    'synonyms' => [
        'dev' => ['developer', 'engineering'],
        'developer' => ['dev', 'engineer'],
        'engineer' => ['engineering', 'developer'],
        'remote' => ['distributed', 'telecommute'],
        'onsite' => ['on-site', 'office'],
        'hr' => ['human resources', 'recruiting'],
        'ui' => ['user interface', 'design'],
        'ux' => ['user experience', 'design'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Weights
    |--------------------------------------------------------------------------
    |
    | Per-field relevance weight used when scoring a job or company against
    | a text query. Higher weight = a match on that field ranks earlier.
    |
    */

    'weights' => [
        'jobs' => [
            'title' => 120,
            'company' => 90,
            'category' => 65,
            'location' => 55,
            'remote_type' => 45,
            'description' => 15,
        ],
        'companies' => [
            'name' => 120,
            'tagline' => 70,
            'location' => 55,
            'mission' => 20,
            'culture' => 15,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Result Limits
    |--------------------------------------------------------------------------
    |
    | Maximum rows materialized per search before in-PHP scoring. Keeps the
    | scoring cost bounded when the query is broad.
    |
    */

    'limits' => [
        'jobs' => 300,
        'companies' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Typo Tolerance
    |--------------------------------------------------------------------------
    |
    | Levenshtein distances for close-match detection in scoring and the
    | "did you mean" correction. Longer queries tolerate a larger distance.
    |
    */

    'typo' => [
        'min_length' => 4,
        'short_query_distance' => 1,
        'long_query_distance' => 2,
        'length_tolerance' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Suggestion Vocabulary
    |--------------------------------------------------------------------------
    |
    | How many titles/names are sampled to build the "did you mean"
    | vocabulary when a query returns zero results.
    |
    */

    'suggestions' => [
        'vocabulary_jobs' => 200,
        'vocabulary_companies' => 100,
    ],
];
