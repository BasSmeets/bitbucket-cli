<?php

namespace App\Commands;

use App\Models\Client\ClientWrapper;
use App\Models\PullRequest;
use Exception;
use LaravelZero\Framework\Commands\Command;

class ApprovePullRequest extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'pr:approve
    {id : id of the pullrequest to approve, use comma separated for multiple ids (required)}';

    /** @var ClientWrapper */
    protected $client;

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Approves a Pr';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->task('Approve PullRequest', function() {
            $this->init();
            $ids = explode(',', $this->argument('id'));
            foreach ($ids as $id) {
                try {
                    $pr = new PullRequest($this->client, ['id' => $id]);
                    $pr->approve();
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                    return false;
                }
            }
            return true;
        });

    }

    public function init()
    {
        $this->client = new ClientWrapper();
    }
}
