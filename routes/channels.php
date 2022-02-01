<?php

use App\Models\Ticket;
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
    return $user_id == auth()->user()->id;
});
Broadcast::channel("ticket_socket", function () {
    return true;
});
Broadcast::channel("transaction_socket.{user_id}", function ($user, $user_id) {

    return $user->id === $user_id;
});

Broadcast::channel("comment_socket.{ticket_id}", function ($ticket_id, $user) {
    // error_log("" . $user->user_type);
    if (auth()->user()->user_type == 2 || auth()->user()->user_type == 1) {
        $ticket = Ticket::find($ticket_id);
    } else {
        $ticket = Ticket::where("user_id", auth()->user()->id)->find($ticket_id);
    }
    // return true;
    return $ticket != null;
});