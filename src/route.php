<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('HierarchyProfit.agent.{id}', function ($user, $id) {
    return $user->id === $id;
 });