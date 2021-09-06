<?php

namespace App\Commands;

use App\Models\Client\ClientWrapper;
use App\Models\PullRequest;
use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository;
use Exception;
use LaravelZero\Framework\Commands\Command;
use CzProject\GitPhp\Git;

class CreatePullRequest extends Command
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
    protected $description = 'Creates a pull request';

    /** @var ClientWrapper */
    protected $client;
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
        $this->client = new ClientWrapper();
        $this->git = new Git();
        $this->repo = $this->git->open(getcwd());
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->init();
        if ($this->option('push') == true) {
            $this->task('Pushing to repo',  function() {
                return $this->pushToRepo();
            });
        }
        $this->task('Creating PullRequest', function() {
            try {
                $pr = new PullRequest($this->client, array_merge($this->arguments(), $this->options()), $this->repo);
                $pr->create();
            } catch (Exception $e) {
                $this->error($e->getMessage());
                return false;
            }
            return true;
        });
    }

    /**
     * @return bool
     */
    protected function pushToRepo()
    {
        try {
            $this->repo->push();
        } catch (GitException $e) {
            $this->error($e->getMessage());
            return false;
        }
        return true;
    }
}
