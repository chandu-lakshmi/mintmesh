<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SeedUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::table('users')->insert(
			array(
					array('username' => 'admin', 'password' => Hash::make('admin'), 'emailid' => 'admin@mintmesh.com'),
					array('username' => 'mintmeshuser', 'password' => Hash::make('password'), 'emailid' => 'mintmesh@mintmesh.com'),
			));
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::table('users')->delete();
	}

}
