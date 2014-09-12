<?php namespace Laravel\Liferaft\Services;

use Github\ResultPager;
use Illuminate\Events\Dispatcher;
use Github\Client as GithubClient;
use Illuminate\Support\Collection;
use Laravel\Liferaft\Contracts\Github as GithubContract;
use Github\Exception\TwoFactorAuthenticationRequiredException;

class KnpGithub implements GithubContract {

	/**
	 * The underlying Github client.
	 *
	 * @var \Github\Client
	 */
	protected $client;

	/**
	 * The event dispatcher instance.
	 *
	 * @param  Dispatcher
	 */
	protected $dispatcher;

	/**
	 * Create a new Github implementation.
	 *
	 * @param  GithubClient  $client
	 * @
	 */
	public function __construct(GithubClient $client, Dispatcher $event)
	{
		$this->event = $event;
		$this->client = $client;
	}

	/**
	 * {@inheritdoc}
	 */
	public function authenticateWithPassword($username, $password)
	{
		$this->client->authenticate($username, $password, GithubClient::AUTH_HTTP_PASSWORD);
	}

	/**
	 * {@inheritdoc}
	 */
	public function findAuthorization($name, $twoFactorAuthCode = null)
	{
		try
		{
			if ($twoFactorAuthCode)
			{
				$this->client->setHeaders(['X-GitHub-OTP' => $twoFactorAuthCode ]);
			}

			return $this->findMatchingAuthorizationToken($name);
		}
		catch (TwoFactorAuthenticationRequiredException $e)
		{
			throw new \InvalidArgumentException('Incorrect or missing Two-Factor Auth Code.');
		}
	}

	protected function findMatchingAuthorizationToken($name)
	{
		$paginator = new ResultPager($this->client);

		$authorizations = $paginator->fetchAll($this->client->api('authorizations'), 'all');

		foreach ($authorizations as $auth)
		{
			if (isset($auth['app']['name']) && strpos($auth['app']['name'], $name) === 0)
			{
				return $auth['token'];
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function createAuthorization($name, $scopes, $twoFactorAuthCode = null)
	{
		try
		{
			$response = $this->client->api('authorizations')->create([
				'note' => $name,
				'scopes' => $scopes,
			], $twoFactorAuthCode);

			return $response['token'];
		}
		catch (TwoFactorAuthenticationRequiredException $e)
		{
			throw new \InvalidArgumentException('Incorrect or missing Two-Factor Auth Code.');
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getUsername()
	{
		return $this->client->api('current_user')->show()['login'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function fork($owner, $repository)
	{
		$this->client->api('repo')->forks()->create($owner, $repository);
	}

	/**
	 * {@inheritdoc}
	 */
	public function rename($owner, $repository, $name)
	{
		$this->client->api('repo')->update($owner, $repository, ['name' => $name]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function sendPullRequest($owner, $branch, $toBranch, $liferaftFile)
	{
		$response = $this->client->api('pull_request')->create(TARGET_OWNER, TARGET_REPOSITORY, [
			'base' => $toBranch,
			'head' => $owner.':'.$branch,
			'title' => '[Liferaft] '.$this->getPullRequestTitle($liferaftFile),
			'body' => $this->getPullRequestBody($liferaftFile),
		]);

		return $response['html_url'];
	}

	/**
	 * Get the title for the pull request from the Liferaft file.
	 *
	 * @param  string  $contents
	 * @return string
	 */
	protected function getPullRequestTitle($contents)
	{
		return strtok($contents, "\n");
	}

	/**
	 * Get the body for the pull request from the Liferaft file.
	 *
	 * @param  string  $contents
	 * @return string
	 */
	protected function getPullRequestBody($contents)
	{
		return trim(implode("\n", array_slice(explode("\n", $contents), 1)));
	}

	/**
	 * {@inheritdoc}
	 */
	public function deleteRepository($owner, $repository)
	{
		$this->client->api('repo')->remove($owner, $repository);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRandomPullRequestId()
	{
		$open = $this->client->api('pull_request')->all(TARGET_OWNER, TARGET_REPOSITORY, ['state' => 'open']);

		$found = Collection::make($open)->shuffle()->first(function($key, $pull)
		{
			return str_contains(strtolower($pull['title']), '[liferaft]');
		});

		if ($found)
		{
			return (int) $found['number'];
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPullRequest($id)
	{
		if (is_null($id))
		{
			throw new \InvalidArgumentException('Invalid ID.');
		}

		$pull = $this->client->api('pull_request')->show(TARGET_OWNER, TARGET_REPOSITORY, (int) $id);

		if ($pull)
		{
			return [
				'id' => (int) $id,
				'title' => $pull['title'],
				'user' => $pull['user']['login'],
				'url' => $pull['html_url'],
				'from_branch' => $pull['head']['ref'],
				'to_branch' => $pull['base']['ref']
			];
		}

		throw new \InvalidArgumentException('ID Not Found.');
	}

}
