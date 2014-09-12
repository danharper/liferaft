<?php namespace Laravel\Liferaft;

use Laravel\Liferaft\Actions\AuthLiferaft;
use Laravel\Liferaft\Contracts\Action;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;

class AuthCommand extends BaseCommand {

	/**
	 * The action instance.
	 *
	 * @var Action
	 */
	protected $action;

	/**
	 * Create a new command instance.
	 *
	 * @param  AuthLiferaft  $action
	 * @return void
	 */
	public function __construct(AuthLiferaft $action)
	{
		parent::__construct();

		$this->action = $action;
	}

	public function run(InputInterface $input, OutputInterface $output)
	{
		$this->input = $input;

		$this->output = $output;

		return parent::run($input, $output);
	}

	/**
	 * Configure the command options.
	 *
	 * @return void
	 */
	protected function configure()
	{
		$this->setName('auth')
			->setDescription('Set your Github personal access token')
			->addArgument('token', InputArgument::OPTIONAL, 'Your Github personal access token.');

		$this->setHelperSet(new HelperSet([ new QuestionHelper ]));
	}

	/**
	 * Execute the command.
	 *
	 * @param  \Symfony\Component\Console\Input\InputInterface  $input
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @return void
	 */
	public function execute(InputInterface $input, OutputInterface $output)
	{
		if ($token = $input->getArgument('token'))
		{
			$this->action->execute($token);
		}
		else
		{
			$this->authWithUsername();
		}
	}

	/**
	 * Authenticate with GitHub, create an application and use that token
	 * 
	 * @return void
	 */
	protected function authWithUsername()
	{
		$this->comment('Your credentials will be exchanged for a token, your password will NOT be stored.');

		$username = $this->ask('GitHub Username:');
		$password = $this->secret('GitHub Password:') ?: '';

		try
		{
			$this->action->execute($username, $password);
		}
		catch (\InvalidArgumentException $e)
		{
			$twoFactorAuthCode = $this->promptTwoFactorAuthCode();

			$this->action->execute($username, $password, $twoFactorAuthCode);
		}
	}

	/**
	 * Prompt for a Two-Factor Auth code
	 * 
	 * @return string
	 */
	protected function promptTwoFactorAuthCode()
	{
		$this->comment('You have Two-Factor Authentication enabled.');
		$this->comment('Please enter the code provided to you via SMS or your GitHub mobile app.');

		return $this->ask('GitHub Two-Factor Auth Code:');

	}

	protected function comment($message)
	{
		$this->output->writeln("<comment>$message</comment>");
	}

	protected function secret($question, $fallback = true)
	{
		$helper = $this->getHelperSet()->get('question');

		$question = new Question("<question>$question</question>");

		$question->setHidden(true)->setHiddenFallback($fallback);

		return $helper->ask($this->input, $this->output, $question);
	}

	protected function ask($question, $default = null)
	{
		$helper = $this->getHelperSet()->get('question');

		$question = new Question("<question>$question</question>", $default);

		return $helper->ask($this->input, $this->output, $question);
	}

}
