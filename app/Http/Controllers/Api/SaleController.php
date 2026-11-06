<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use App\Models\SaleDetail;
use App\Http\Requests\SaleRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;
class SaleController extends Controller
{

    public function index(){
        $sales=Sale::with(['saleDetails.product:id,name'])->get();
        $data = $sales->map(function($sale){
            return [
                'id'=>$sale->id,
                'total'=>(float) $sale->total,
                'total_discount'=>(float) $sale->total_discount,
                'created_at'=>$sale->created_at->toDateString(),
                'details'=>$sale->saleDetails->map(function($detail){
                    return [
                        'product'=>$detail->product->name,
                        'quantity'=>$detail->quantity,
                        'subtotal'=>(float) $detail->subtotal,
                        'discount_percent'=>(float) $detail->discount_percent,
                        'discount_amount'=>(float) $detail->discount_amount,
                        'subtotal_with_discount'=>(float) $detail->subtotal_with_discount
                    ];
                })
            ];
        });
        return response()->json($data,200);
    }

    public function store(SaleRequest $request){
        try{
            $validated=$request->validated();
            $sale= (new Sale)->getConnection()->transaction(function () use ($validated){
                $items= collect($validated['items']);
                $productIds= $items->pluck('product_id')->sort()->values();
                $products=Product::whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                if($products->count()!==$productIds->count()){
                    throw ValidationException::withMessages(['items'=>'Uno o más productos no existen.',]);
                }
                $totalCents=0;
                $totalDiscountCents=0;
                $detailData=[];
                foreach($items as $item){
                    $product=$products->get($item['product_id']);
                    $quantity=$item['quantity'];
                    $discountPercent=(float)($item['discount_percent']??0);

                    if ($product->stock<$quantity){
                        throw ValidationException::withMessages(['items'=>"El producto {$product->name} no tiene suficiente stock."]);
                    }            
                
                $priceCents=(int) round((float)$product->price*100);
                $subtotalCents=$priceCents*$quantity;
                $discountBasisPoints = (int) round ($discountPercent * 100);
                $discountCents=(int) round(($subtotalCents*$discountBasisPoints)/10000);
                $subtotalWithDiscountCents=$subtotalCents-$discountCents;
                $totalCents+=$subtotalWithDiscountCents;
                $totalDiscountCents+=$discountCents;
                $detailData[]=[
                    'product_id'=>$product->id,
                    'quantity'=>$quantity,
                    'price'=>$priceCents/100,
                    'subtotal'=>$subtotalCents/100,
                    'discount_percent'=>$discountPercent,
                    'discount_amount'=>$discountCents/100,
                    'subtotal_with_discount'=>$subtotalWithDiscountCents/100,
                    ];
                }
                $sale=Sale::create([
                    'user_id'=>$validated['user_id'],
                    'total'=>$totalCents/100,
                    'total_discount'=>$totalDiscountCents/100,
                    ]); 
                $sale->saleDetails()->createMany($detailData);                  
                foreach($items as $item){
                    $products->get($item['product_id'])->decrement('stock',$item['quantity']);
                }return $sale;
            });
            return response()->json(['message' => 'Venta creada correctamente','sale_id' => $sale->id,'total' => (float) $sale->total, 'total_discount' => (float) $sale->total_discount,],201);
        }catch(ValidationException $e){
            throw $e;
       }catch(Throwable $e){   
            Log::error('Error al registrar la venta', ['error'=>$e->getMessage()]);
            return response()->json(['message'=>'Ocurrio un error al registrar la venta'],500);
        }      
    }

    public function destroy($id)
    {
        try {
            $sale = Sale::with('saleDetails')->find($id);
            if (!$sale) {
                return response()->json(['message' => 'La venta que se intenta eliminar no ha sido encontrada'], 404);
            }
            $stockDevuelto = [];
            try {
                foreach ($sale->saleDetails as $detail) {
                    $product = Product::find($detail->product_id);
                    if ($product) {
                        $product->stock = $product->stock + $detail->quantity;
                        $product->save();
                        $stockDevuelto[] = [
                            'product_id' => $product->id,
                            'quantity' => $detail->quantity,
                        ];
                    }
                }
                foreach ($sale->saleDetails as $detail) {
                    $detail->delete();
                }
                $sale->delete();
                return response()->json(['message' => 'Venta eliminada correctamente'], 200);

            } catch (\Exception $e) {
                foreach ($stockDevuelto as $item) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        $product->stock = $product->stock - $item['quantity'];
                        $product->save();
                    }
                }
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Ocurrio un error al eliminar la venta'], 500);
        }
    }
}
