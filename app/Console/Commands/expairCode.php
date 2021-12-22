<?php

namespace App\Console\Commands;

use App\Models\UserCode;
use Carbon\Carbon;
use Illuminate\Console\Command;

class expairCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expair:code';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'expaired user code after 3 minute';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $codes = UserCode::where("created_at", "<=", Carbon::now()->subMinute(3))->get();
        // subtract 3 minute in time now and cheack this
        foreach ($codes as $code) {
            $code->delete();
        }
    }
}