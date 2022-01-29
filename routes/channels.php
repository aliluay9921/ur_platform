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
Broadcast::channel("company_socket", function () {
    return true;
});
Broadcast::channel("payment_socket", function () {
    return true;
});
Broadcast::channel("notification_socket.{user_id}", function ($user_id, $user) {
    return $user_id == $user->id;
});
Broadcast::channel("ticket_socket.{ticket_id}", function ($ticket_id, $user) {
    return $ticket_id;
});
Broadcast::channel("transaction_socket.{user_id}", function ($user_id, $user) {
    return $user_id == $user->id;
});