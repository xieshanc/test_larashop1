<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Installment;
use App\Models\InstallmentItem;
use Illuminate\Http\Request;
use App\Models\InvalidRequestException;
use App\Events\OrderPaid;
use Carbon\Carbon;

class InstallmentsController extends Controller
{
    public function index(Request $request)
    {
        $installments = Installment::query()
            ->where('user_id', $request->user()->id)
            ->paginate(10);
        return view('installments.index', ['installments' => $installments]);
    }

    public function show(Installment $installment)
    {
        $this->authorize($installment);
        $items = $installment->items()->orderBy('sequence')->get();
        return view('installments.show', [
            'installment'   => $installment,
            'items'         => $items,
            'nextItem'      => $items->where('paid_at', null)->first(),
        ]);
    }

    public function payByAlipay(Installment $installment)
    {
        if ($installment->order->closed) {
            throw new InvalidRequestException('订单已关闭');
        }
        if ($installment->status === Installment::STATUS_FINISHED) {
            throw new InvalidRequestException('订单已结清');
        }
        if (!$nextItem = $installment->items()->whereNull('paid_at')->orderBy('sequence')->first()) {
            throw new InvalidRequestException('分期订单已结清');
        }

        return app('alipay')->web([
            'out_trade_no'  => $installment->no . '_' . $nextItem->sequence,
            'total_amount'  => $nextItem->total,
            'subject'       => '支付 Larashop 的分期订单：' . $installment->no,
            // 'notify_url'    => '', // 此处定义会覆盖 AppServiceProvider 里的
            'return_url'    => route('installments.show', ['installment' => $installment->id]), // 此处定义会覆盖 AppServiceProvider 里的
        ]);
    }

    public function alipayReturn()
    {
        try {
            app('alipay')->verify();
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
        if ($this->paid($data->out_trade_no, 'alipay', $data->trade_no)) {
            return app('alipay')->success();
        }
        return 'fail';
    }

    public function wechatNotify()
    {
        $data = app('wechat_pay')->verify();
        if ($this->paid($data->out_trade_no, 'wechat', $data->transaction_id)) {
            return app('wechat_pay')->success();
        }
        return 'fail';
    }



    /**
     * @param  [type] $outTradeNo    [本项目定义的单号]
     * @param  [type] $paymentMethod [支付方式]
     * @param  [type] $paymentNo     [支付的平台定义的单号]
     */
    protected function paid($outTradeNo, $paymentMethod, $paymentNo)
    {
        list($no, $sequence) = explode('_', $outTradeNo);

        if (!$installment = Installment::where('no', $no)->first()) {
            return false;
        }
        if (!$item = $installment->items()->where('sequence', $sequence)->first()) {
            return false;
        }

        if ($item->paid_at) {
            return app('alipay')->success();
        } else {
            \DB::transaction(function () use ($no, $installment, $item, $paymentNo) {
                $item->update([
                    'paid_at'           => Carbon::now(),
                    'payment_method'    => 'alipay',
                    'payment_no'        => $paymentNo,
                ]);

                if ($item->sequence === 0) {
                    $installment->update(['status' => Installment::STATUS_REPAYING]);
                    $installment->order->update([
                        'paid_at'           => Carbon::now(),
                        'payment_method'    => 'installment',
                        'payment_no'        => $no,
                    ]);
                    event(new OrderPaid($installment->order));
                }

                if ($item->sequence === $installment->count - 1) {
                    $installment->update(['status' => Installment::STATUS_FINISHED]);
                }

            });
            return app('alipay')->success();
        }
    }

    public function wechatRefundNotify(Request $request)
    {
        // 给微信的失败响应
        $failXml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';

        $data = app('wechat_pay')->verify(null, true);
        list($no, $sequence) = explode('_', $data['out_refund_no']);

        // $order_id = select * from `orders` where `refund_no` = {$no};
        // $installment_id = select * from `installments` where `order_id` = {$order_id};
        // select * from `installment_items` where `installment_id` = {$installment_id} and `sequence` = {$sequence};

        // select * from `installment_items` where exists(select * from `installments` where `installment_items`.`installment_id` = `installments`.`id` and exists(select * from `orders` where `installments`.`order_id` = `orders`.`id` and `orders`.`refund_no` = '20200409162525042961'));

        $item = InstallmentItem::query()
            ->whereHas('installment', function ($query) use ($no) {
                $query->whereHas('order', function ($query) use ($no) {
                    $query->where('refund_no', $no);
                });
            })
            ->where('sequence', $sequence)
            ->first();

        if (!$item) {
            return $failXml;
        }

        if ($data['refund_status'] === 'SUCCESS') {
            $item->update([
                'refund_status' => InstallmentItem::REFUND_STATUS_SUCCESS,
            ]);
            $item->installment->refreshRefundStatus();
        } else {
            $item->update([
                'refund_status' => InstallmentItem::REFUND_STATUS_FAILED,
            ]);
        }
        return app('wechat_pay')->success();





    }
}
