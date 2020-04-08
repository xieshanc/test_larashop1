<?php

namespace App\Admin\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\HandleRefundRequest;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use App\Exceptions\InternalException;
use App\Exceptions\InvalidRequestException;
use App\Services\OrderService;

class OrdersController extends AdminController
{
    use ValidatesRequests;
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '订单';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Order);

        $grid->model()->whereNotNull('paid_at')->orderBy('paid_at', 'desc');

        $grid->id('ID');
        $grid->no('订单流水号');
        $grid->column('user.name', '买家'); // 关联
        $grid->total_amount('总金额')->sortable();
        $grid->paid_at('支付时间')->sortable();
        $grid->ship_status('物流')->display(function ($value) {
            return Order::$shipStatusMap[$value];
        });
        $grid->refund_status('退款状态')->display(function ($value) {
            return Order::$refundStatusMap[$value];
        });

        $grid->disableCreateButton();   // 禁用创建
        $grid->actions(function ($actions) {
            $actions->disableDelete();  // 禁用删除
            $actions->disableEdit();    // 禁用编辑
        });
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete(); // 禁用批量删除
            });
        });

        return $grid;
    }

    public function show($id, Content $content)
    {
        $order = Order::find($id);
        return $content->header('查看订单')->body(view('admin.orders.show', ['order' => $order]));
    }

    public function ship(Order $order, Request $request)
    {
        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未付款');
        }

        if ($order->ship_status !== Order::SHIP_STATUS_PENDING) {
            throw new InvalidRequestException('该订单已发货');
        }

        if ($order->type === Order::TYPE_CROWDFUNDING &&
            $order->items[0]->product->crowdfunding->status != CrowdfundingProduct::STATUS_SUCCESS) {
            throw new InvalidRequestException('众筹成功后才能发货');
        }

        $data = $this->validate($request, [
            'express_company' => ['required'],
            'express_no'      => ['required'],
        ], [], [
            'express_company'   => '物流公司',
            'express_no'        => '物流单号'
        ]);

        // 将订单发货状态改为已发货，并存入物流信息
        $order->update([
            'ship_status' => Order::SHIP_STATUS_DELIVERED,
            'ship_data'   => $data,
        ]);

        return redirect()->back();
    }

    public function handleRefund(Order $order, HandleRefundRequest $request, OrderService $orderService)
    {
        if ($order->refund_status !== Order::REFUND_STATUS_APPLIED) {
            throw new InvalidRequestsException('订单状态不正确');
        }

        if ($request->input('agree')) {
            $extra = $order->extra ?: [];
            unset($extra['refund_disagree_reason']);
            $order->update([
                'extra' => $extra,
            ]);
            $orderService->refundOrder($order);
        } else {
            $extra = $order->extra ?: [];
            $extra['refund_disagree_reason'] = $request->input('reason');
            $order->update([
                'refund_status' => Order::REFUND_STATUS_PENDING,
                'extra'         => $extra,
            ]);
        }
        return $order;
    }

    protected function _refundOrder(Order $order)
    {

    }


}
