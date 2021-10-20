<?php

namespace App\Commands;

use App\Models\Client\ClientWrapper;
use App\Models\PullRequest;
use Exception;
use LaravelZero\Framework\Commands\Command;

class MergeApprovePullRequest extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'pr:amerge
    {id : id of the pullrequest to approve and merge, use comma separated for multiple ids (required)}
    {--close= : Close the source branch after merge (optional)}
    {--strat= : Sets the merge strategy (optional)}';

    /** @var ClientWrapper */
    protected $client;

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Merges a Pr';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->task('Approve and Merge PullRequest', function() {
            $this->init();
            $ids = explode(',', $this->argument('id'));
            foreach ($ids as $id) {
                try {
                    $options = array_merge($this->options(), $this->arguments(), ['id' => $id]);
                    $options['close'] = true;
                    $options['strat'] = 'merge_commit'; //TODO this is hardcoded now.
                    $pr = new PullRequest($this->client, $options);
                    $pr->approve();
                    $pr->merge();
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
