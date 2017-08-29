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
                
                $this->app->bind('Mintmesh\Repositories\API\Enterprise\EnterpriseRepository',
                        'Mintmesh\Repositories\API\Enterprise\EloquentEnterpriseRepository');
                
                $this->app->bind('Mintmesh\Repositories\API\Enterprise\NeoEnterpriseRepository',
                        'Mintmesh\Repositories\API\Enterprise\NeoeloquentEnterpriseRepository');

                $this->app->bind('Mintmesh\Repositories\API\Post\NeoPostRepository',
                        'Mintmesh\Repositories\API\Post\NeoeloquentPostRepository');
                
                $this->app->bind('Mintmesh\Repositories\API\Candidates\CandidatesRepository',
                        'Mintmesh\Repositories\API\Candidates\EloquentCandidatesRepository',
                        'Mintmesh\Repositories\API\Candidates\NeoCandidatesRepository',
                        'Mintmesh\Repositories\API\Candidates\NeoeloquentCandidatesRepository');
                
                $this->app->bind('Mintmesh\Repositories\API\Globals\NeoGlobalRepository',
                        'Mintmesh\Repositories\API\Globals\NeoeloquentGlobalRepository');
	}

}
?>
