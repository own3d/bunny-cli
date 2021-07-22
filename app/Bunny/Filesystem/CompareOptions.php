<?php


namespace App\Bunny\Filesystem;


class CompareOptions
{
    const START = 'start';
    const NO_LOCK_VERIFICATION = 'no_lock_verification';
    const NO_LOCK_GENERATION = 'no_lock_generation';
    const LOCK_FILE = 'lock_file';
    const DRY_RUN = 'dry_run';
}
