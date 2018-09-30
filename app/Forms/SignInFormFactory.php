<?php

declare(strict_types=1);

namespace App\Forms;

use App\Authentication\Credentials;
use App\Authentication\IdentityFactory;
use App\Authentication\UserAuthenticator;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nette\Security\User;

final class SignInFormFactory
{
	/**
	 * @var FormFactory
	 */
	private $factory;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var UserAuthenticator
	 */
	private $userAuthenticator;

	/**
	 * @var IdentityFactory
	 */
	private $identityFactory;


	public function __construct(
		FormFactory $factory,
		User $user,
		UserAuthenticator $userAuthenticator,
		IdentityFactory $identityFactory
	) {
		$this->factory = $factory;
		$this->user = $user;
		$this->userAuthenticator = $userAuthenticator;
		$this->identityFactory = $identityFactory;
	}


	public function create(callable $onSuccess): Form
	{
		$form = $this->factory->create();
		$form->addText('username', 'Username:')
			->setRequired('Please enter your username.');

		$form->addPassword('password', 'Password:')
			->setRequired('Please enter your password.');

		$form->addCheckbox('remember', 'Keep me signed in');

		$form->addSubmit('send', 'Sign in');

		$form->onSuccess[] = function(Form $form, $values) use ($onSuccess): void {
			try {
				$userData = $this->userAuthenticator->authenticate(new Credentials(
					$values->username,
					$values->password
				));

				$this->user->setExpiration($values->remember ? '14 days' : '20 minutes');
				$this->user->login($this->identityFactory->createFromUserData($userData));
			} catch (AuthenticationException $e) {
				$form->addError('The username or password you entered is incorrect.');

				return;
			}

			$onSuccess();
		};

		return $form;
	}
}
