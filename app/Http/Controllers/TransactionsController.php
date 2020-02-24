<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Transaction;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $transactions = Transaction::all();
        if ($transactions->count() > 0){
            return Transaction::with('order')->get();
        }
        return [];
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $transaction = Transaction::find($id);
        if($transaction != null){
            $transaction->order = Transaction::find($id)->order;
            return $transaction;
        }else{
           return response()->json(['errorMessage' => "No transaction with that ID = " . $id], 404);
        }
    }

       /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
 

    // Callback URL to update the transaction
    public function update(Request $request, $id)
    {

        $transaction = [
            'id' => $request->id,
            'status' => $request->status,
            'amount' => $request->amount
        ];

        return [
            'transaction' => $transaction,
            'id' => $id
        ];
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
