<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:expire')->daily()->at('00:05');
