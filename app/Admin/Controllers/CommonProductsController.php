<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use App\Models\Product;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use App\Jobs\SyncOneProductToESJob;

abstract class CommonProductsController extends AdminController
{
    abstract public function getProductType();

    protected function grid()
    {
        $grid = new Grid(new Product());

        $grid->model()->where('type', $this->getProductType())->orderBy('id', 'desc');
        $this->customGrid($grid);

        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        return $grid;
    }

    abstract protected function customGrid(Grid $grid);

    protected function form()
    {
        $form = new Form(new Product);

        $form->hidden('type')->value($this->getProductType());
        $form->text('title', '商品名')->rules('required');
        $form->text('long_title', '商品长标题')->rules('required');
        $form->select('category_id', '分类')->options(function ($id) {
            $category = Category::find($id);
            if ($category) {
                return [$category->id => $category->full_name];
            }
        })->ajax('/admin/api/categories?is_directory=0');

        $form->image('image', '图片')->rules('required|image');
        $form->quill('description', '商品描述')->rules('required');
        $form->radio('on_sale', '上架')->options(['1' => '是', '0' => '否'])->default('0');

        $this->customForm($form);

        $form->hasMany('skus', 'SKU 列表', function (Form\NestedForm $form) {
            $form->text('title', 'SKU 名称')->rules('required');
            $form->text('description', 'SKU 描述')->rules('required');
            $form->text('price', '单价')->rules('required|numeric|min:0.01');
            $form->text('stock', '剩余库存')->rules('required|integer|min:0');
        });

        $form->hasMany('properties', '商品属性', function (Form\NestedForm $form) {
            $form->text('name', '属性名')->rules('required');
            $form->text('value', '属性值')->rules('required');
        });

        $form->saving(function (Form $form) {
            $form->model()->price = collect($form->input('skus'))->where(Form::REMOVE_FLAG_NAME, 0)->min('price') ?: 0;
        });

        $form->saved(function (Form $form) {
            $product = $form->model();
            dispatch(new SyncOneProductToESJob($product));
        });
        return $form;
    }

    abstract protected function customForm(Form $form);
}
