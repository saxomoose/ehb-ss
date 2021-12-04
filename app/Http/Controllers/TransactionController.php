<?php

namespace App\Http\Controllers;

use App\Http\Resources\ItemResource;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return TransactionResource::collection(Transaction::all());
    }

    /**
     * Different from other entities in that transaction is only created together with pivot table records. In other words, a transaction should not exist without at least 1 matching row in item_transaction.
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'data.*' => 'required|array:item_id,quantity',
            'data.*.item_id' => ['required', Rule::exists('items', 'id')],
            'data.*.quantity' => 'required|integer|min:1' // max restriction?
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rawValidatedAttributes = $validator->validated();
        $validatedAttributes = $rawValidatedAttributes['data'];

        // Iteration with value modification.
        // $collection = collect($validatedAttributes);
        // $collection->transform(function ($item, $key) {
        //     $item['applied_price'] = str_replace('.', '', $item['applied_price']);
        //     return $item;
        // });

        $transaction = DB::transaction(function () use ($request, $user, $validatedAttributes) {
            $transaction = Transaction::create([
                'user_id' => $user->id
            ]);

            foreach ($validatedAttributes as $line) {
                DB::table('item_transaction')->insert([
                    ['transaction_id' => $transaction->id, 'item_id' => $line['item_id'], 'quantity' => $line['quantity'], 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
                ]);
            }

            $resultSet = DB::table('item_transaction as it')
                ->join('items as i', 'i.id', '=', 'it.item_id')
                ->select(DB::raw('sum((i.price * it.quantity)) as subtotal, round(sum((i.price * (i.vat_rate/100 + 1) * it.quantity))) as total'))
                ->groupBy('it.transaction_id')
                ->having('it.transaction_id', '=', $transaction->id)
                ->get();
            //compute subtotal and total 
            // $subtotal = DB::table('item_transaction')
            //     ->where('transaction_id', '=', $transaction->id)
            //     ->sum('extended_price');

            // $total = bcmul($subtotal, ($request->user()->vat_rate / 100) + 1, 0); // Gets the event-wide vat rate from the token.

            $resultSetObject = $resultSet->first();

            $transaction->update([
                'subtotal' => $resultSetObject->subtotal,
                'total' => $resultSetObject->total
            ]);

            return $transaction;
        });

        return (new TransactionResource($transaction))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction)
    {
        return new TransactionResource($transaction);
    }

    /**
     * Should not be implemented.
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function update(Request $request)
    // {
    //     return response()->json(['error' => 'The transaction cannot be updated.'], Response::HTTP_NOT_IMPLEMENTED);
    // }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return response()->noContent();
    }

    public function items(Request $request, Transaction $transaction)
    {
        return ItemResource::collection($transaction->items);
    }

    public function toggleStatus(Transaction $transaction)
    {
        if ($request->user()->tokenCan('self') && $transaction->user_id !== $request->user()->id) {
            return response()->json(['error' => 'The user is only authorised to modify his/her own record(s)'], Response::HTTP_UNAUTHORIZED);
        }

        if ($transaction->status === 'outstanding') {
            $transaction->status = 'paid';

            return response()->noContent();
        }
        // Admin and managers are able to modify status of transaction.
        else if ($request->user()->tokenCan('*') || $request->user()->tokenCan('write')) {

            $transaction->status = 'outstanding';

            return reponse()->noContent();
        } else {
            return response()->json(['error' => 'This user cannot modify the status of this transaction.'], Response::HTTP_UNAUTHORIZED);
        }
    }
}