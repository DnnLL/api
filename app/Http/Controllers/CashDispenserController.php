<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Bill;
use Illuminate\Support\Facades\DB;
/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Cash Dispenser API",
 *      description="API para manejo de depósitos y retiros automáticos",
 *      @OA\Contact(
 *          email="soporte@ejemplo.com"
 *      )
 * )
 */
class CashDispenserController extends Controller
{
    /**
     * @OA\Post(
     *     path="/deposit",
     *     summary="Depositar billetes a la cuenta",
     *     description="Permite depositar billetes a una cuenta específica.",
     *    operationId="deposit",
     *    security={{"token":{}}},
     *     tags={"Cash Dispenser"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"account_number","denomination","quantity"},
     *             @OA\Property(property="account_number", type="string", example="ACC12345"),
     *             @OA\Property(property="denomination", type="integer", example=50),
     *             @OA\Property(property="quantity", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Depósito exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Deposit successful."),
     *             @OA\Property(property="new_balance", type="number", format="float", example=500.00)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Cuenta o denominación inválida")
     * )
     */
    public function deposit(Request $request)
{
    $request->validate([
        'account_number' => 'required|string',
        'denomination' => 'required|integer',
        'quantity' => 'required|integer|min:1'
    ]);

    $account = Account::where('account_number', $request->account_number)->first();
    $bill = Bill::where('denomination', $request->denomination)->first();

    if (!$account || !$bill) {
        return response()->json(['message' => 'Invalid account or denomination.'], 404);
    }

    // Actualizar la cantidad de billetes
    $bill->quantity += $request->quantity;
    $bill->save();

    // Actualizar el balance de la cuenta
    $account->balance += ($request->denomination * $request->quantity);
    $account->save();

    // Obtener la memoria del cajero
    $memory = Bill::orderBy('denomination', 'desc')->get(['denomination', 'quantity']);

    return response()->json([
        'message' => 'Deposit successful.',
        'new_balance' => $account->balance,
        'cash_dispenser_memory' => $memory
    ]);
}


 /**
 * @OA\Post(
 *     path="/withdraw",
 *     summary="Retirar dinero de la cuenta",
 *     tags={"Cash Dispenser"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"account_number","amount"},
 *             @OA\Property(property="account_number", type="string", example="ACC12345"),
 *             @OA\Property(property="amount", type="number", format="float", example=150.00)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Retiro exitoso",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Withdrawal successful."),
 *             @OA\Property(property="dispensed_bills", type="object",
 *                 @OA\Property(property="50", type="integer", example=2),
 *                 @OA\Property(property="20", type="integer", example=1)
 *             ),
 *             @OA\Property(property="new_balance", type="number", format="float", example=350.00),
 *             @OA\Property(property="cash_dispenser_memory", type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="denomination", type="integer"),
 *                     @OA\Property(property="quantity", type="integer")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=404, description="Fondos insuficientes o cuenta inválida"),
 *     @OA\Response(response=400, description="No se puede dispensar el monto exacto")
 * )
 */
public function withdraw(Request $request)
{
    $request->validate([
        'account_number' => 'required|string',
        'amount' => 'required|numeric|min:1'
    ]);

    $account = Account::where('account_number', $request->account_number)->first();

    if (!$account || $account->balance < $request->amount) {
        return response()->json(['message' => 'Insufficient funds or invalid account.'], 402);
    }

    $remainingAmount = $request->amount;
    $bills = Bill::orderBy('denomination', 'desc')->get();
    $dispensedBills = [];

    foreach ($bills as $bill) {
        if ($remainingAmount >= $bill->denomination && $bill->quantity > 0) {
            $billCount = min(intval($remainingAmount / $bill->denomination), $bill->quantity);

            if ($billCount > 0) {
                $dispensedBills[$bill->denomination] = $billCount;
                $remainingAmount -= $billCount * $bill->denomination;
                $bill->quantity -= $billCount;
                $bill->save();
            }
        }
    }

    if ($remainingAmount > 0) {
        return response()->json(['message' => 'Unable to dispense exact amount with available bills.'], 400);
    }

    // Actualizar el balance de la cuenta
    $account->balance -= $request->amount;
    $account->save();

    // Obtener la memoria del cajero después del retiro
    $memory = Bill::orderBy('denomination', 'desc')->get(['denomination', 'quantity']);

    return response()->json([
        'message' => 'Withdrawal successful.',
        'dispensed_bills' => $dispensedBills,
        'new_balance' => $account->balance,
        'cash_dispenser_memory' => $memory
    ]);
}
    /**
     * @OA\Get(
     *     path="/bills",
     *     summary="Obtener el estado actual de los billetes",
     *     tags={"Cash Dispenser"},
     *     @OA\Response(
     *         response=200,
     *         description="Listado de billetes con su cantidad disponible",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="denomination", type="integer", example=50),
     *                 @OA\Property(property="quantity", type="integer", example=100)
     *             )
     *         )
     *     )
     * )
     */
    public function getBills()
    {
        $bills = Bill::orderBy('denomination', 'desc')->get(['denomination', 'quantity']);
        return response()->json($bills);
    }

}