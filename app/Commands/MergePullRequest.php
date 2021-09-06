<?php

namespace App\Commands;

use App\Models\Client\ClientWrapper;
use App\Models\PullRequest;
use Exception;
use LaravelZero\Framework\Commands\Command;

class MergePullRequest extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'pr:merge
    {id : id of the pullrequest to merge, use comma separated for multiple ids (required)}
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
        $this->task('Merge PullRequest', function() {
            $this->init();
            $ids = explode(',', $this->argument('id'));
            foreach ($ids as $id) {
                try {
                    $options = array_merge($this->options(), $this->arguments(), ['id' => $id]);
                    $options['strat'] = 'merge_commit'; //TODO this is hardcoded now.
                    $pr = new PullRequest($this->client, $options);
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
