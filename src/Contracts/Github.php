<?php namespace Laravel\Liferaft\Contracts;

interface Github {

	/**
	 * Authenticate with password
	 * @param  string $username
	 * @param  string $password
	 * @return void
	 */
	public function authenticateWithPassword($username, $password);

	/**
	 * Create an application on Github
	 * @param  string $name
	 * @param  string[] $scopes
	 * @param  string|null $tfaCode
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function createAuthorization($name, $scopes, $tfaCode = null);

	/**
	 * Get the authenticated user's usernmae.
	 *
	 * @return string
	 */
	public function getUsername();

	/**
	 * Fork the given repository.
	 *
	 * @param  string  $owner
	 * @param  string  $repository
	 * @return void
	 */
	public function fork($owner, $repository);

	/**
	 * Rename the given repository.
	 *
	 * @param  string  $owner
	 * @param  string  $repository
	 * @param  string  $name
	 * @return void
	 */
	public function rename($owner, $repository, $name);

	/**
	 * Send a pull request back to Laravel.
	 *
	 * @param  string  $owner
	 * @param  string  $branch
	 * @param  string  $toBranch
	 * @param  string  $liferaftFile
	 * @return string
	 */
	public function sendPullRequest($owner, $branch, $toBranch, $liferaftFile);

	/**
	 * Delete the given repository from Github.
	 *
	 * @param  string  $owner
	 * @param  string  $repository
	 * @return void
	 */
	public function deleteRepository($owner, $repository);

	/**
	 * Get a random pull request ID.
	 *
	 * @return int
	 */
	public function getRandomPullRequestId();

	/**
	 * Get a pull request.
	 *
	 * @param  int  $id
	 * @return array
	 */
	public function getPullRequest($id);

}
