<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\OrderPaymentRequest;
use Illuminate\Validation\Rule;
use App\Models\Order;
use App\Models\Installment;
use App\Exception\InvalidRequestException;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use App\Events\OrderPaid;

class PaymentController extends Controller
{
    public function payByInstallment(Order $order, Request $request)
    {
        $this->authorize('own', $order);
        if ($order->paid_at && $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }
        if ($order->total_amount < config('app.min_installment_amount')) {
            throw new InvalidRequestException('钱太少了，不能分期');
        }
        $this->validate($request, [
            'count' => ['required', Rule::in(array_keys(config('app.installment_fee_rate')))],
        ]);

        Installment::query()
            ->where('order_id', $order->id)
            ->where('status', Installment::STATUS_PENDING)
            ->delete();
        $count = $request->input('count');
        $installment = new Installment([
            'total_amount'  => $order->total_amount,
            'count'         => $count,
            'fee_rate'      => config('app.installment_fee_rate')[$count],
            'fine_rate'     => config('app.installment_fine_rate'),
        ]);
        $installment->user()->associate($request->user());
        $installment->order()->associate($order);
        $installment->save();

        $dueDate = Carbon::tomorrow();

        $base = big_number($order->total_amount)->divide($count)->getValue();
        $fee = big_number($base)->multiply($installment->fee_rate)->divide(100)->getValue();
        for ($i = 0; $i < $count; $i++) {
            if ($i === $count - 1) {
                $base = big_number($order->total_amount)->subtract(big_number($base)->multiply($count - 1));
            }
            $installment->items()->create([
                'sequence'  => $i,
                'base'      => $base,
                'fee'       => $fee,
                'due_date'  => $dueDate,
            ]);
            $dueDate = $dueDate->copy()->addDays(30);
        }
        return $installment;
    }

    public function payByAlipay(Order $order, OrderPaymentRequest $request)
    {
        $this->authorize('own', $order);

        return app('alipay')->web([
            'out_trade_no' => $order->no,
            'total_amount' => $order->total_amount,
            'subject'      => '支付 LaraShop 的订单：' . $order->no,
            'return_url'   => route('orders.show', ['order' => $order]),
            // 'return_url'   => route('payment.alipay.return', ['order' => $order->id]),
        ]);
    }

    public function alipayReturn(Order $order)
    {
        try {
            $data = app('alipay')->verify();
        } catch (\Exception $e) {
            return view('pages.error', ['msg' => '数据不正确']);
        }
        return redirect()->route('orders.show', ['order' => $order]);

    }

    public function alipayNotify()
    {
        $data = app('alipay')->verify();
        $order = Order::where('no', $data->out_trade_no)->first();

        if (!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success();
        }

        if (!$order) return 'fail';
        if ($order->paid_at) {
            // return app('alipay')->success();
        } else {
            $order->update([
                'paid_at'           => Carbon::now(),
                'payment_method'    => 'alipay',
                'payment_no'        => $data->trade_no,
            ]);
            $this->afterPaid($order);
        }

        return app('alipay')->success();
    }

    public function payByWechat(Order $order, Request $request) {
        // 校验权限
        $this->authorize('own', $order);
        // 校验订单状态
        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }
        // scan 方法为拉起微信扫码支付
        return app('wechat_pay')->scan([
            'out_trade_no' => $order->no,  // 商户订单流水号，与支付宝 out_trade_no 一样
            'total_fee' => $order->total_amount * 100, // 与支付宝不同，微信支付的金额单位是分。
            'body'      => '支付 Laravel Shop 的订单：'.$order->no, // 订单描述
        ]);
    }

    public function wechatRefundNotify(Request $request)
    {
        // 给微信的失败响应
        $failXml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
        $data = app('wechat_pay')->verify(null, true);

        // 没有找到对应的订单，原则上不可能发生，保证代码健壮性
        if(!$order = Order::where('no', $data['out_trade_no'])->first()) {
            return $failXml;
        }

        if ($data['refund_status'] === 'SUCCESS') {
            // 退款成功，将订单退款状态改成退款成功
            $order->update([
                'refund_status' => Order::REFUND_STATUS_SUCCESS,
            ]);
        } else {
            // 退款失败，将具体状态存入 extra 字段，并表退款状态改成失败
            $extra = $order->extra;
            $extra['refund_failed_code'] = $data['refund_status'];
            $order->update([
                'refund_status' => Order::REFUND_STATUS_FAILED,
                'extra' => $extra
            ]);
        }

        return app('wechat_pay')->success();
    }

    public function wechatNotify()
    {
        // 校验回调参数是否正确
        $data  = app('wechat_pay')->verify();
        // 找到对应的订单
        $order = Order::where('no', $data->out_trade_no)->first();
        // 订单不存在则告知微信支付
        if (!$order) {
            return 'fail';
        }
        // 订单已支付
        if ($order->paid_at) {
            // 告知微信支付此订单已处理
            return app('wechat_pay')->success();
        }

        // 将订单标记为已支付
        $order->update([
            'paid_at'        => Carbon::now(),
            'payment_method' => 'wechat',
            'payment_no'     => $data->transaction_id,
        ]);

        return app('wechat_pay')->success();
    }

    public function afterPaid(Order $order)
    {
        event(new OrderPaid($order));
    }

    public function paidReturn()
    {

    }
}

