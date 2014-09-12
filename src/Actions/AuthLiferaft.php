<?php namespace Laravel\Liferaft\Actions;

use Laravel\Liferaft\Git;
use Illuminate\Events\Dispatcher;
use Laravel\Liferaft\Contracts\Action;
use Laravel\Liferaft\Contracts\Github;

class AuthLiferaft implements Action {

	use ActionTrait;

	/**
	 * The Github implementation.
	 *
	 * @var Github
	 */
	protected $github;

	/**
	 * Create a new action instance.
	 *
	 * @param  Github  $github
	 * @param  Dispatcher  $dispatcher
	 * @return void
	 */
	public function __construct(Github $github, Dispatcher $event)
	{
		$this->github = $github;
		$this->event = $event;
	}

	/**
	 * Execute the action.
	 *
	 * @return void
	 */
	public function execute($username, $password = null, $twoFactorAuthCode = null)
	{
		if ($username && $password === null && $twoFactorAuthCode === null)
		{
			$token = $username;
		}
		else
		{
			$token = $this->findOrCreateToken($username, $password, $twoFactorAuthCode);
		}

		$this->storeToken($token);
	}

	/**
	 * Store the GitHub Token for later use
	 * 
	 * @param  string $token
	 * @return void
	 */
	protected function storeToken($token)
	{
		$this->task('Storing GitHub Token...', function() use ($token)
		{
			file_put_contents(__DIR__.'/../../liferaft.json', json_encode(['token' => $token]));
		});
	}

	/**
	 * Find or create our application's authorization on GitHub
	 * 
	 * @param  string $username
	 * @param  string $password
	 * @param  string|null $twoFactorAuthCode
	 * @return string
	 */
	protected function findOrCreateToken($username, $password, $twoFactorAuthCode = null)
	{
		$this->github->authenticateWithPassword($username, $password);

		$applicationName = 'Laravel Liferaft';

		$token = $this->findToken($applicationName, $twoFactorAuthCode);

		if ( ! $token)
		{
			$this->createToken($applicationName, $twoFactorAuthCode);
		}

		return $token;
	}

	/**
	 * Find an existing token for our application's authorization on GitHub
	 * 
	 * @param  string $applicationName
	 * @param  string|null $twoFactorAuthCode
	 * @return string
	 */
	protected function findToken($applicationName, $twoFactorAuthCode = null)
	{
		$token = $this->github->findAuthorization($applicationName, $twoFactorAuthCode);

		return $token;
	}

	/**
	 * Create an authorization with GitHub, retrieving the token
	 * 
	 * @param  string $applicationName
	 * @param  string|null $twoFactorAuthCode
	 * @return string
	 */
	protected function createToken($applicationName, $twoFactorAuthCode = null)
	{
		$authScopes = ['public_repo', 'user'];

		$token = $this->github->createAuthorization($applicationName, $authScopes, $twoFactorAuthCode);

		return $token;
	}

}