<?php

namespace App\Admin\Controllers;

use App\Models\Product;
use App\Models\ProductSku;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class SeckillProductsController extends CommonProductsController
{
    public function getProductType()
    {
        return Product::TYPE_SECKILL;
    }

    protected function customGrid(Grid $grid)
    {
        $grid->id('ID')->sortable();
        $grid->title('商品名称');
        $grid->on_sale('已上架')->display(function ($value) {
            return $value ? '是' : '否';
        });
        $grid->price('价格');
        $grid->column('seckill.start_at', '开始时间');
        $grid->column('seckill.end_at', '结束时间');
        $grid->sold_count('销量');
    }

    protected function customForm(Form $form)
    {
        $form->datetime('seckill.start_at', '秒杀开始时间')->rules('required|date');
        $form->datetime('seckill.end_at', '秒杀结束时间')->rules('required|date');

        $form->saved(function (Form $form) {
            $product = $form->model();
            $product->load(['seckill', 'skus']);
            $diff = $product->seckill->end_at->getTimestamp() - time();

            $product->skus->each(function (ProductSku $sku) use ($diff, $product) {
                if ($product->on_sale && $diff > 0) {
                    Redis::setex('seckill_sku_' . $sku->id, $diff, $sku->stock);
                } else {
                    Redis::del('seckill_sku_' . $sku->id);
                }
            });
        });
    }
}
