<?php

namespace App\Listeners;

use DB;
use App\Models\OrderItem;
use App\Events\OrderReviewedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\OrderService;

class UpdateProductRatingListener implements ShouldQueue
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
     * @param  OrderReviewedEvent  $event
     * @return void
     */
    public function handle(OrderReviewedEvent $event)
    {
        $order = $event->getOrder();
        app(OrderService::class)->updateProductRating($order);
    }
}
