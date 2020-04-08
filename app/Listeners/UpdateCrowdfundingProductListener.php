<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateCrowdfundingProductListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  OrderPaid  $event
     * @return void
     */
    public function handle(OrderPaid $event)
    {
        $order = $event->getorder();
        if ($order->type !== Order::TYPE_CROWDFUNDING) {
            return;
        }

        $crowdfunding = $order->items[0]->product->crowdfunding;

        $data = Order::query()
            ->where('type', Order::TYPE_CROWDFUNDING)
            ->whereNotNull('paid_at')
            ->whereHas('items', function ($query) use ($crowdfunding) {
                $query->where('product_id', $crowdfunding->product_id);
            })
            ->first([
                \DB::raw('sum(total_amount) as total_amount'),
                \DB::raw('count(distinct(user_id)) as user_count'),
            ]);

        // select sum(total_amount),count(distinct(user_id)) from `orders` where `paid_at` IS NOT NULL and exists(select * from `order_items` where `order_items`.`order_id` = `orders`.`id` and `order_items`.`product_id` = 11)

        $crowdfunding->update([
            'total_amount'  => $data->total_amount,
            'user_count'    => $data->user_count,
        ]);
    }
}
