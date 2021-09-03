<?php

namespace App\Commands;

use Bitbucket\Client;
use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository;
use LaravelZero\Framework\Commands\Command;
use Http\Client\Exception;
use Illuminate\Support\Facades\Cache;
use CzProject\GitPhp\Git;
use Dotenv\Dotenv;


class PrCreate extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'pr:create
    {target : Branch where the pr should be created to (required)}
    {--close= : Close branch after merge (optional)}
    {--push= : git push to remote before pr (optional)}';

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

    /** @var array of json payload */
    protected $params;

    /** @var Git git wrapper */
    protected $git;
    /** @var GitRepository */
    protected $repo;

    /**
     * json with default reviewers in order to create the pr.
     * @var string
     */
    public $defaultReviewers;

    public function init()
    {
        $this->client = new Client();
        $this->git = new Git();
        $dotEnv = Dotenv::createImmutable(getcwd(), '.bb.env');
        $dotEnv->load();
        $this->repo = $this->git->open(getcwd());
    }

    public function generatePayload()
    {
        $lastCommit = $this->repo->getLastCommit();
        $lastCommitMsg = $lastCommit->getSubject();
        $branch = $this->repo->getCurrentBranchName();
        $targetBranch = $this->argument('target');
        $close = $this->hasOption('close') == true;
        $this->params = [
            'title' => $lastCommitMsg,
            'close_source_branch' => $close,
            'source' => [
                'branch' => [
                    'name' => $branch
                ]
            ],
            'destination' => [
                'branch' => [
                    'name' => $targetBranch
                ]
            ]
        ];
        if ($this->defaultReviewers) {
            $this->params['reviewers'] = $this->defaultReviewers;
        }
        return true;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->init();
        if ($this->hasOption('push') == true) {
            $this->task('Pushing to repo',  function() {
                return $this->pushToRepo();
            });
        }
        $this->task('Authenticating with bitbucket API',  function() {
            return $this->authenticate();
        });
        $this->task('Gathering default reviewers', function() {
            return $this->getDefaultReviewers();
        });
        $this->task('Generating payload message',  function() {
            return $this->generatePayload();
        });
        $this->task('Creating PullRequest', function() {
            return $this->createPullRequest();
        });
    }

    public function pushToRepo()
    {
        try {
            $this->repo->push();
        } catch (GitException $e) {
            return false;
        }
        return true;
    }

    /**
     * Authenticate to bitbucket api
     * @return bool
     */
    public function authenticate(): bool
    {
        try {
            $pass = ENV('BB_PASSWORD');
            $username = ENV('BB_USERNAME');
            $this->client->authenticate(
                Client::AUTH_HTTP_PASSWORD,
                $username,
                $pass
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getDefaultReviewers()
    {
        if ($dr = Cache::get('default_reviewers')) {
            $this->defaultReviewers = $dr;
        }
        try {
            $params = [
                'pagelen' => 100,
            ];
            $result = $this->client->repositories()
                ->workspaces(ENV('BB_WORKSPACE'))
                ->defaultReviewers(ENV('BB_REPO'))
                ->list($params);
            $this->defaultReviewers = $this->getDefaultReviewersFormatted($result['values']);
            Cache::put('default_reviewers', $this->defaultReviewers, now()->addDay());
            return true;
        } catch (Exception $e) {
           $this->error($e->getMessage());
           return false;
        }
    }

    public function getDefaultReviewersFormatted($result)
    {
        $reviewers = [];
        foreach ($result as $item) {
            $reviewers[] = ['uuid' => $item['uuid']];
        }
        unset($reviewers[0]);
        return $reviewers;
    }

    public function createPullRequest()
    {
        try {
            $this->client->repositories()
                ->workspaces(ENV('BB_WORKSPACE'))
                ->pullRequests(ENV('BB_REPO'))
                ->create($this->params);
            return true;
        } catch (Exception $e) {
            $this->error('Failed to create PullRequest');
            $this->error($e->getMessage());
            return false;
        }
    }
}
