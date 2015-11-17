<?php namespace Mintmesh\ServiceProviders\API\User;

use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider {

	public function register()
	{
		// Bind the users repository interface to our Eloquent-specific implementation
		// This service provider is called every time the application starts
		$this->app->bind(
			'Mintmesh\Repositories\API\User\UserRepository',
			'Mintmesh\Repositories\API\User\EloquentUserRepository',
                        'Mintmesh\Services\Validators\API\User\UserValidator',
                        'Mintmesh\Services\Emails\API\User\UserEmailManager',
                        'Mintmesh\Services\FileUploader\API\User\UserFileUploader'
		);
                $this->app->bind('Mintmesh\Repositories\API\User\NeoUserRepository',
                        'Mintmesh\Repositories\API\User\NeoeloquentUserRepository');
                $this->app->bind(
			'Mintmesh\Repositories\API\SocialContacts\ContactsRepository',
			'Mintmesh\Repositories\API\SocialContacts\NeoeloquentContactsRepository',
                        'Mintmesh\Services\Validators\API\SocialContacts\ContactsValidator'
		);
                $this->app->bind('Mintmesh\Repositories\API\Referrals\ReferralsRepository',
                     'Mintmesh\Repositories\API\Referrals\NeoeloquentReferralsRepository');
                
                $this->app->bind('Mintmesh\Repositories\API\Payment\PaymentRepository',
                     'Mintmesh\Repositories\API\Payment\EloquentPaymentRepository');
                $this->app->bind('Mintmesh\Repositories\API\SMS\SMSRepository',
                     'Mintmesh\Repositories\API\SMS\EloquentSMSRepository');
	}

}
?>
