<?php

namespace App\Http\Controllers\Frontend;

use App\Helpers\IyzicoAddressHelper;
use App\Helpers\IyzicoBuyerHelper;
use App\Helpers\IyzicoOptionsHelper;
use App\Helpers\IyzicoPaymentCardHelper;
use App\Helpers\IyzicoRequestHelper;
use App\Http\Controllers\Controller;
use App\Mail\CarRented;
use App\Models\Car;
use App\Models\Cart;
use App\Models\CreditCard;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Payment;

class CheckoutController extends Controller
{
    /**
     * Shows the payment form
     *
     * @return View
     */
    public function showCheckoutForm(): View
    {
        return view("frontend.cart.checkout_form");
    }

    public function checkout(Request $request): View
    {
        $creditCard = new CreditCard();
        $data = $this->prepare($request, $creditCard->getFillable());
        $creditCard->fill($data);

        // Sepetteki ürünlerin toplam tutarını hesapla
        $total = $this->calculateCartTotal();

        // Sepeti getir
        $cart = $this->getOrCreateCart();

        // Ödeme isteği oluştur
        $request = IyzicoRequestHelper::createRequest($cart, $total);

        // PaymentCard Nesnesini oluştur.
        $paymentCard = IyzicoPaymentCardHelper::getPaymentCard($creditCard);
        $request->setPaymentCard($paymentCard);

        // ALıcı nesnesini oluştur
        $buyer = IyzicoBuyerHelper::getBuyer();
        $request->setBuyer($buyer);

        // Kargo adresi nesnelerini oluştur.
        $shippingAddress = IyzicoAddressHelper::getAddress();
        $request->setShippingAddress($shippingAddress);

        // Fatura adresi nesnelerini oluştur.
        $billingAddress = IyzicoAddressHelper::getAddress();
        $request->setBillingAddress($billingAddress);

        // Sepetteki ürünleri (CartDetails) BasketItem listesi olarak hazırla
        $basketItems = $this->getBasketItems();
        $request->setBasketItems($basketItems);

        //Options Nesnesi Oluştur
        $options = IyzicoOptionsHelper::getTestOptions();

        // Ödeme yap
        $payment = Payment::create($request, $options);

        // İşlem başarılı ise sipariş ve fatura oluştur.
        if ($payment->getStatus() == "success") {

            // Sepeti sona erdir.
            $this->finalizeCart($cart);

            // Sipariş oluştur
            $order = $this->createOrderWithDetails($cart);

            //Fatura Oluştur
            $this->createInvoiceWithDetails($order);


            return view("frontend.checkout.success");

        } else {
            $errorMessage = $payment->getErrorMessage();
            return view("frontend.checkout.error", ["message" => $errorMessage]);
        }
    }

    private function calculateCartTotal(): float
    {
        $total = 0;
        $cart = $this->getOrCreateCart();
        $cartDetails = $cart->details;
        foreach ($cartDetails as $detail) {
            $total += $detail->car->price * $detail->quantity;
        }

        return $total;
    }

    private function getOrCreateCart(): Cart
    {
        $user = Auth::user();
        $cart = Cart::firstOrCreate(
            ['user_id' => $user->user_id, 'is_active' => true],
            ['code' => Str::random(8)]
        );
        return $cart;
    }

    private function getBasketItems(): array
    {
        $basketItems = [];
        $cart = $this->getOrCreateCart();
        $cartDetails = $cart->details;

        foreach ($cartDetails as $detail) {
            $item = new BasketItem();
            $item->setId($detail->car->car_id);
            $item->setName($detail->car->name);
            $item->setCategory1($detail->car->category->name);
            $item->setItemType(BasketItemType::PHYSICAL);
            $item->setPrice($detail->car->price);

            for ($i = 0; $i < $detail->quantity; $i++) {
                $basketItems[] = $item;
            }
        }

        return $basketItems;
    }

    private function finalizeCart(Cart $cart)
    {
        $cart->is_active = false;
        $cart->save();
    }

    private function createOrderWithDetails(Cart $cart): Order
    {
        $order = new Order([
            "cart_id" => $cart->cart_id,
            "code" => $cart->code
        ]);
        $order->save();

        foreach ($cart->details as $detail) {
            $order->details()->create([
                'order_id' => $order->order_id,
                'car_id' => $detail->car_id,
                'quantity' => $detail->quantity
            ]);
        }

        return $order;
    }

    private function createInvoiceWithDetails(Order $order)
    {
        $invoice = Invoice::create([
            'order_id' => $order->order_id,
            "cart_id" => $order->order_id,
            "code" => $order->code
        ]);

        //Fatura Detaylarını Ekle
        foreach ($order->details as $detail) {
            $invoice->details()->create([
                'car_id' => $detail->car_id,
                'quantity' => $detail->quantity,
                'unit_price' => $detail->car->price,
                'total' => ($detail->quantity * $detail->car->price),
            ]);
            Mail::to($detail->car->user)->send(new CarRented(auth()->user()));
            Car::where('car_id', $detail->car_id)->update([
                'is_active' => false
            ]);
        }
    }
}
