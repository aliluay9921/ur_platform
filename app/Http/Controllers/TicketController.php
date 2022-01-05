<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Notifications;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Traits\Pagination;
use App\Traits\UploadImage;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    use SendResponse, Pagination, UploadImage;
    public function openTicket(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "body" => "required",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $data = [];
        $data = [
            "body" => $request["body"],
            "user_id" => auth()->user()->id
        ];
        $ticket = Ticket::create($data);
        if (array_key_exists("image", $request)) {
            Image::create([
                "target_id" => $ticket->id,
                "image" => $this->uploadPicture($request["image"], '/images/ticketImages/')
            ]);
        }

        return $this->send_response(200, trans("message.open.ticket"), [], Ticket::find($ticket->id));
    }

    public function getTickets()
    {
        if (isset($_GET["ticket_id"])) {
            $comments = Ticket::with("comments")->find($_GET["ticket_id"]);
            return $this->send_response(200, trans("message.get.content.ticket"), [], $comments);
        }

        if (auth()->user()->user_type == 2 || auth()->user()->user_type == 1) {
            $tickets = Ticket::select("*");
        } elseif (auth()->user()->user_type == 0) {
            $tickets = Ticket::where("user_id", auth()->user()->id);
        }
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            $tickets->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {
            $tickets->where(function ($q) {
                $columns = Schema::getColumnListing('tickets');
                foreach ($columns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $_GET['query'] . '%');
                }
            });
        }
        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key == 'skip' || $key == 'limit' || $key == 'query' || $key == 'filter') {
                    continue;
                } else {
                    $sort = $value == 'true' ? 'desc' : 'asc';
                    $tickets->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($tickets,  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, trans("message.get.open.tickets"), [], $res["model"], null, $res["count"]);
    }

    public function addCommentTicket(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "body" => "required",
            "ticket_id" => "required|exists:tickets,id"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $data = [];
        $data = [
            "body" => $request["body"],
            "ticket_id" => $request["ticket_id"],
            "user_id" => auth()->user()->id,
        ];
        $comment = TicketComment::create($data);
        Notifications::create([
            "title" => trans("message.notifications.add.comment.ticket"),
            "body" => $request["body"],
            "target_id" => $comment->id,
            "to_user" => $comment->user_id,
            "from_user" => auth()->user()->id
        ]);
        if (array_key_exists("image", $request)) {
            Image::create([
                "target_id" => $comment->id,
                "image" => $this->uploadPicture($request["image"], "/images/ticketImages/")
            ]);
        }

        return $this->send_response(200, trans("message.add.comment"), [], TicketComment::find($comment->id));
    }

    public function closeTicket(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "ticket_id" => "required|exists:tickets,id"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $ticket = Ticket::find($request["ticket_id"]);
        if (auth()->user()->id == $ticket->user_id || auth()->user()->user_type == 1 || auth()->user()->user_type == 2) {
            $ticket->update([
                "active" => false
            ]);
            if (auth()->user()->id != $ticket->user_id) {
                Notifications::create([
                    "title" => trans("message.notifications.close.ticket"),
                    "target_id" => $ticket->id,
                    "to_user" => $ticket->user_id,
                    "from_user" => auth()->user()->id
                ]);
            }
            return $this->send_response(200, trans("message.close.ticket"), [], Ticket::find($request["ticket_id"]));
        } else {
            return $this->send_response(400, trans("message.error.close.ticket"), [], []);
        }
    }

    public function deleteComment(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:ticket_comments,id"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $comment = TicketComment::find($request["id"]);
        if (auth()->user()->id == $comment->user_id || auth()->user()->user_type == 1 || auth()->user()->user_type == 2) {
            $comment->delete();
            return $this->send_response(200, trans("message.delete.comment"), [], []);
        } else {
            return $this->send_response(200, trans("message.error.delete.comment"), [], []);
        }
    }
}