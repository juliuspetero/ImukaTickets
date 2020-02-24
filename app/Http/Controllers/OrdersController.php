<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests\SaveOrderRequest;
use App\Order;
use App\Transaction;
use App\Ticket;
use Beyonic;
use Beyonic_Payment;

// Beyonic::setApiVersion("v1");
Beyonic::setApiKey('b8be7097955059a2235aa71ef526a4dbfd04fb37');
class OrdersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $orders = Order::all();
        if ($orders->count() > 0){
            return Order::with('transaction')->with('tickets')->get();
        }
        return [];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SaveOrderRequest $request)
    {
        // return $request;

        $tickets = ($request->tickets);

        $description = 'Payment for';
         // Create the message for the transaction
         foreach ($tickets as $key => $ticket) {
            $eventTitle = Ticket::find($ticket['id'])->event->title;
            if($key == 0)
                $description = $description . ' ' . $eventTitle;
            else
                $description = $description . ', ' . $eventTitle;
        }

        $payment = Beyonic_Payment::create([
            "phonenumber" => $request->phoneNumber,
            "first_name" =>$request->firstName ,
            "last_name" => $request->lastName,
            "amount" => $request->totalCost,
            "currency" => "UGX",
            "description" => $description,
            "payment_type" => "money",
            "callback_url" => "https://app.imuka.co/api/transactions",
            "metadata" => ["email" => $request->email]
        ], ["Duplicate-Check-Key" => "ab594c14986612f6167a"]);
          
        print_r($payment);  // Examine the returned object
        return;
          
        // Make request to beyonic, if the transaction failed, do not save the order to the database
        $transactionResponse = [
            'id' => substr(md5(rand()), 0, 20),
            'status' => 'success',
            'amount' => $request->totalCost
        ];

        // Persist the order to the DB
        $order = new Order();
        $order->firstName = $request->firstName;
        $order->lastName = $request->lastName;
        $order->email = $request->email;
        $order->totalCost = $request->totalCost;
        $order->phoneNumber = $request->phoneNumber;
        $order->transaction_id =$transactionResponse['id'];
        $order->save();

        // Create the pivot table record
        foreach ($tickets as $ticket) {
            $order->tickets()->attach($ticket['id'], ['numberOfTickets' => $ticket['numberOfTickets']]); 
        }
        
        // Persist the transaction to the database
        $transaction = new Transaction();
        $transaction->id = $transactionResponse['id'];
        $transaction->status = $transactionResponse['status'];
        $transaction->amount = $transactionResponse['amount'];
        $transaction->order_id = $order->id;
        $transaction->save();

        $order->tickets = $tickets;
        $order->transaction = $transaction;
        return $order;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order = Order::find($id);
        if($order != null){
            $order->transaction = $order->transaction;
            $order->tickets = $order->tickets;
            return $order;
        }else{
           return response()->json(['errorMessage' => "No order with that ID = " . $id], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
