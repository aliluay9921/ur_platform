<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel("company_socket.{company_id}", function ($company_id) {
    return $company_id;
});
Broadcast::channel("payment_socket.{payment_id}", function ($payment_id) {
    return $payment_id;
});
Broadcast::channel("notification_socket.{user_id}", function ($user_id, $user) {
    return $user_id == $user->id;
});
Broadcast::channel("ticket_socket.{user_id}", function ($user_id, $user) {
    return $user_id == $user->id;
});