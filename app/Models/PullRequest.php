<?php
/**
 * Copyright (c) 2020. Dynacommerce B.V.
 */

namespace App\Models;

use CzProject\GitPhp\GitRepository;
use Http\Client\Exception;
use Illuminate\Support\Facades\Cache;
use App\Models\Client\ClientWrapper;

class PullRequest
{
    /** @var ClientWrapper $client */
    public $client;
    /** @var GitRepository $repo */
    public $repo;
    /** @var string $defaultReviewers */
    public $defaultReviewers;
    /** @var array $options */
    public $options;
    /** @var array $payload */
    public $payload;

    /**
     * @param ClientWrapper $client
     * @param GitRepository $repo
     * @param array $options
     * @throws Exception
     */
    public function __construct(ClientWrapper $client, array $options, GitRepository $repo = null)
    {
        $this->client = $client;
        $this->repo = $repo;
        $this->options = $options;
    }

    /**
     * @throws Exception
     */
    public function setDefaultReviewers()
    {
        if ($dr = Cache::get('default_reviewers')) {
            $this->defaultReviewers = $dr;
        }
        $params = [
            'pagelen' => 100,
        ];
        $result = $this->client->repositories()
            ->workspaces(ENV('BB_WORKSPACE'))
            ->defaultReviewers(ENV('BB_REPO'))
            ->list($params);
        $this->defaultReviewers = $this->formatDefaultReviewers($result['values']);
        Cache::put('default_reviewers', $this->defaultReviewers, now()->addDay());
    }

    /**
     * @param $result
     * @return array
     */
    protected function formatDefaultReviewers($result): array
    {
        $reviewers = [];
        foreach ($result as $item) {
            $reviewers[] = ['uuid' => $item['uuid']];
        }
        unset($reviewers[0]);
        return $reviewers;
    }

    /**
     * @throws \CzProject\GitPhp\GitException
     */
    protected function generateCreatePrPayload(): void
    {
        $lastCommit = $this->repo->getLastCommit();
        $lastCommitMsg = $lastCommit->getSubject();
        $branch = $this->repo->getCurrentBranchName();
        $targetBranch = $this->options['target'];
        $close = $this->options['close'];
        $this->payload = [
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
            $this->payload['reviewers'] = $this->defaultReviewers;
        }
    }

    /**
     * @throws Exception
     */
    public function create()
    {
        $this->setDefaultReviewers();
        $this->generateCreatePrPayload();
        $this->client->repositories()
            ->workspaces(ENV('BB_WORKSPACE'))
            ->pullRequests(ENV('BB_REPO'))
            ->create($this->payload);
    }

    public function approve()
    {
        $this->client->repositories()
            ->workspaces(ENV('BB_WORKSPACE'))
            ->pullRequests(ENV('BB_REPO'))
            ->approval($this->options['id'])->approve();
    }

    public function merge() //TODO check if this can be async also so dont have to wait for bb to process the merge.
    {
        $this->client->repositories()
            ->workspaces(ENV('BB_WORKSPACE'))
            ->pullRequests(ENV('BB_REPO'))
            ->merge(
                $this->options['id'],
                [
                    'close_source_branch' => $this->options['close'],
                    'merge_strategy' => $this->options['strat']
                ]
            );
    }

    public function list()
    {
        $list = $this->client->repositories()
            ->workspaces(ENV('BB_WORKSPACE'))
            ->pullRequests(ENV('BB_REPO'))
            ->list([
                'pagelen' => 50,
                'q' => sprintf('title ~ "%s"', $this->options['title']),
                ]
            );
        $ids = $this->extractPrIdsFromListResponse($list);
        if (empty($ids)) {
            throw new \Exception('No PullRequests found for provide title');
        }
        return implode(',', $ids);
    }

    protected function extractPrIdsFromListResponse($list)
    {
        foreach ($list['values'] as $value) {
            $ids[] = $value['id'];
        }
        return $ids ?? [];
    }

}
