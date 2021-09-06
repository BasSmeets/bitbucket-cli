<?php

namespace App\Commands;

use App\Models\Client\ClientWrapper;
use App\Models\PullRequest;
use LaravelZero\Framework\Commands\Command;

class ListApproveMergePullRequest extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'pr:lam
    {title : title for which the pr ids should be fetched, for example ticketId}
    {--close= : Close the source branch after merge (optional)}
    {--strat= : Sets the merge strategy (optional)}';

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
            $this->listPrs();
        });
        $this->info($this->list);
        $answer = $this->ask('Do you want to proceed with approval and merging for above pr id\'s ? (y/n)');
        if ($answer === 'y') {
            $this->approveMergePrs();
        }
    }

    protected function init()
    {
        $this->client = new ClientWrapper();
    }

    protected function listPrs()
    {
        try {
            $options = array_merge($this->arguments(), $this->options());
            $pr = new PullRequest($this->client, $options);
            $this->list = $pr->list();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return false;
        }
        return true;
    }

    protected function approveMergePrs()
    {
        $this->task('Approve and Merge PullRequest', function() {
            $ids = explode(',', $this->list);
            foreach ($ids as $id) {
                try {
                    $options = array_merge($this->options(), $this->arguments(), ['id' => $id]);
                    $options['strat'] = 'merge_commit'; //TODO this is hardcoded now.
                    $pr = new PullRequest($this->client, $options);
                    $pr->approve();
                    $pr->merge();
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                    return false;
                }
            }
            return true;
        });
    }
}
