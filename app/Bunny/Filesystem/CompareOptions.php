<?php


namespace App\Bunny\Filesystem;


class CompareOptions
{
    const START = 'start';
    const NO_SHA256_VERIFICATION = 'no_sha256_verification';
    const NO_SHA256_GENERATION = 'no_sha256_generation';
    const LOCK_FILE = 'lock_file';
    const DRY_RUN = 'dry-run';
}
