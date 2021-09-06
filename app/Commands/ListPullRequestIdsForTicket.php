<?php

namespace App\Commands;

use App\Models\Client\ClientWrapper;
use App\Models\PullRequest;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ListPullRequestIdsForTicket extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'pr:list
    {title : title for which the pr ids should be fetched, for example ticketId}';

    /** @var ClientWrapper */
    protected $client;

    public $list = '';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->init();
        $this->task('Listing PullRequest Id', function() {
            try {
                $options = array_merge($this->arguments(), $this->options());
                $pr = new PullRequest($this->client, $options);
                $this->list = $pr->list();
            } catch (Exception $e) {
                $this->error($e->getMessage());
                return false;
            }
            return true;
        });
        $this->info($this->list);
    }

    public function init()
    {
        $this->client = new ClientWrapper();
    }
}
