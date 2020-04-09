<?php

namespace App\Jobs;

use App\Models\Installment;
use App\Models\InstallmentItem;
use App\Models\Order;
use App\Exceptions\InternalException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefundInstallmentOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->order->payment_method !== 'installment'
            || !$this->order->paid_at
            || $this->order->refund_status !== Order::REFUND_STATUS_PROCESSING) {
            return;
        }

        if (!$installment = Installment::query()->where('order_id', $this->order->id)->first()) {
            return;
        }

        foreach ($installment->items as $item) {
            if (!$item->paid_at || in_array($item->refund_status, [
                InstallmentItem::REFUND_STATUS_SUCCESS,
                InstallmentItem::REFUND_STATUS_PROCESSING,
            ])) {
                continue;
            }

            // 对每笔分期执行退款
            try {
                $this->refundInstallmentItem($item);
            } catch (\Exception $e) {
                \Log::warning('分期退款失败：' . $e->getMessage(), [
                    'installment_item_id' => $item->id,
                ]);
            }
        }

        $installment->refreshRefundStatus();
    }

    protected function refundInstallmentItem(InstallmentItem $item)
    {
        // 造退款号，由订单的退款号拼接期数
        $refundNo = $this->order->refund_no . "_" . $item->sequence;
        switch ($item->payment_method) {
            case 'alipay':
                app('alipay')->refund([
                    'trade_no'          => $item->payment_no,   // 原支付号
                    'refund_amount'     => $item->base,
                    'out_request_no'    => $refundNo,           // 退款号
                ]);
                if ($ret->sub_code) {
                    $item->update([
                        'refund_status' => InstallmentItem::REFUND_STATUS_FAILED,
                    ]);
                } else {
                    $item->update([
                        'refund_status' => InstallmentItem::REFUND_STATUS_SUCCESS,
                    ]);
                }
                break;
            case 'wechat':
                app('wechat_pay')->refund([
                    'transaction_id'    => $item->payment_no,   // 原支付号
                    'total_fee'         => $item->total * 100,
                    'refund_fee'        => $item->base * 100,
                    'out_refund_no'     => $refundNo,           // 退款号
                    'notify_url'        => 'rongodofs',
                ]);
                $item->update([
                    'refund_status' => InstallmentItem::REFUND_STATUS_PROCESSING,
                ]);
                break;
            default:
                throw new InternalException('啥支付方式？' . $item->payment_method);
                break;
        }
    }
}
