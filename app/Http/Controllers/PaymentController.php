<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\OrderPaymentRequest;
use App\Models\Order;
use App\Exception\InvalidRequestException;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use App\Events\OrderPaid;

class PaymentController extends Controller
{
    public function payByAlipay(Order $order, OrderPaymentRequest $request)
    {
        $this->authorize('own', $order);

        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }
        return app('alipay')->web([
            'out_trade_no' => $order->no,
            'total_amount' => $order->total_amount,
            'subject'      => '支付 LaraShop 的订单：' . $order->no,
        ]);
    }

    public function alipayReturn()
    {
        try {
            $data = app('alipay')->verify();
        } catch (\Exception $e) {
            return view('pages.error', ['msg' => '数据不正确']);
        }
        return view('pages.success', ['msg' => '付款成功']);

    }

    public function alipayNotify()
    {
        $data = app('alipay')->verify();

        if (!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success();
        }
        $order = Order::where('no', $data->out_trade_no)->first();

        if (!$order) return 'fail';
        if ($order->paid_at) {
            return app('alipay')->success();
        }

        $order->update([
            'paid_at'           => Carbon::now(),
            'payment_method'    => 'alipay',
            'payment_no'        => $data->trade_no,
        ]);

        $this->afterPaid($order);
        return app('alipay')->success();
    }

    public function payByWechat(Order $order, Request $request)
    {
        // $url = $wechatOrder->code_url;
        $url = 'https://item.taobao.com/item.htm?spm=a1z0d.6639537.1997196601.28.5a297484Uuz3sm&id=614510466848';
        $qrCode = new QrCode($url);

        // 将生成的二维码图片数据以字符串形式输出，并带上相应的响应类型
        return response($qrCode->writeString(), 200, ['Content-Type' => $qrCode->getContentType()]);
    }

    public function afterPaid(Order $order)
    {
        event(new OrderPaid($order));
    }
}
