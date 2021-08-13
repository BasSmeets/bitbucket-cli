<?php

namespace App\Commands;

use Bitbucket\Client;
use LaravelZero\Framework\Commands\Command;
use Http\Client\Exception;
use Illuminate\Support\Facades\Cache;


class PrCreate extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    // @todo add arguments to close source, include default reviewers and rebase
    // accept the targetbranch as argument
    protected $signature = 'pr:create {target : Branch where the pr should be created to (required)}{--close= : Close branch after merge (optional)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * bitbucket client
     *  @var Client
     */
    protected $client;

    /**
     * json with default reviewers in order to create the pr.
     * @var string
     */
    public $defaultReviewers;

    public function init()
    {
        $this->client = new Client();
        $this->path = getcwd();
        // @todo add check to see if git is installed.
        $this->currentBranch = shell_exec('git branch --show-current');
        echo $this->currentBranch;
        $this->lastCommit = shell_exec('git log --format="%H" -n 1');
        echo $this->lastCommit;
        $this->lastCommitMessage = shell_exec('git log --format=%B -n 1' . $this->lastCommit);
        echo $this->lastCommitMessage;
        $this->targetBranch = $this->argument('target');
        echo $this->targetBranch;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->init();
        $this->task('Authenticating with bitbucket API',  function() {
            return $this->authenticate();
        });
        $this->task('Gathering default reviewers', function() {
            return $this->getDefaultReviewers();
        });
        $this->task('Creating PullRequest', function() {
            return $this->createPullRequest();
        });
    }

    /**
     * Authenticate to bitbucket api
     * @return bool
     */
    public function authenticate(): bool
    {
        $pass = ENV('BB_PASSWORD');
        $username = ENV('BB_USERNAME');
        $this->client->authenticate(
            Client::AUTH_HTTP_PASSWORD,
            $username,
            $pass
        );
        return true;
    }

    public function getDefaultReviewers()
    {
        Cache::flush();
        if ($dr = Cache::get('default_reviewers')) {
            $this->defaultReviewers = $dr;
        }
        try {
            $params = [
                'pagelen' => 100,
            ];
            $this->defaultReviewers = $this->client->repositories()
                ->workspaces(ENV('BB_WORKSPACE'))
                ->defaultReviewers(ENV('BB_REPO'))
                ->list($params);
            Cache::put('default_reviewers', $this->defaultReviewers, now()->addDay());
            return true;
        } catch (Exception $e) {
           $this->error($e->getMessage());
           return false;
        }
    }

    public function createPullRequest()
    {
        try {
//            $this->client->repositories()
//                ->workspaces(ENV('BB_WORKSPACE'))
//                ->pullRequests(ENV('BB_REPO'))
//                ->create();
            return true;
        } catch (Exception $e) {
            $this->error('Failed to create PullRequest');
            $this->error($e->getMessage());
            return false;
        }
    }
}
