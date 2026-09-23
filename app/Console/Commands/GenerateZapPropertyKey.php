<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateZapPropertyKey extends Command
{
    protected $signature = 'zapproperty:key';

    protected $description = 'Generate a platform key for the ZapProperty portal (set it as ZAPPROPERTY_API_KEY here and ZAPTASK_API_KEY on ZapProperty)';

    public function handle(): int
    {
        $key = 'zp_live_'.Str::random(40);

        $this->line($key);
        $this->newLine();
        $this->info('Set it on both sides — it is not stored anywhere by this command:');
        $this->line("  ZapTask (Heroku):      heroku config:set ZAPPROPERTY_API_KEY={$key}");
        $this->line("  ZapProperty (Vercel):  ZAPTASK_API_KEY={$key}");

        return self::SUCCESS;
    }
}
