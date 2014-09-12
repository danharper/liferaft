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
	public function execute($username, $password = null, $tfaCode = null)
	{
		if ($username && $password === null && $tfaCode === null)
		{
			$token = $username;
		}
		else
		{
			$token = $this->createToken($username, $password, $tfaCode);
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
	 * Create an authorization with GitHub, retrieving the token
	 * 
	 * @param  string $username
	 * @param  string $password
	 * @param  string|null $tfaCode
	 * @return string
	 */
	protected function createToken($username, $password, $tfaCode = null)
	{
		$this->github->authenticateWithPassword($username, $password);

		$applicationName = 'Laravel Liferaft';

		$authScopes = ['public_repo', 'user'];

		$token = $this->github->createAuthorization($applicationName, $authScopes, $tfaCode);

		return $token;
	}

}