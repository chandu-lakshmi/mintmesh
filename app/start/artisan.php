<?php

/*
|--------------------------------------------------------------------------
| Register The Artisan Commands
|--------------------------------------------------------------------------
|
| Each available Artisan command must be registered with the console so
| that it is available to be called. We'll register every command so
| the console gets access to each of the command object instances.
|
*/
Artisan::add(new job1);
Artisan::add(new job2);
Artisan::add(new job3);
Artisan::add(new parserJob);
Artisan::add(new testJob);
Artisan::add(new hcmJob);
Artisan::add(new zenefits);
Artisan::add(new icims);
