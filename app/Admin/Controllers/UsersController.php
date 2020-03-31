<?php

namespace App\Admin\Controllers;

use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class UsersController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'App\Models\User';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User);

        $grid->id('ID');
        $grid->column('name', __('Name'));
        $grid->email('邮箱')->display(function ($value) {
            return $value;
        });
        $grid->email_verified_at('已验证邮箱')->display(function ($value) {
            return $value ? '是' : '否';
        });
        $grid->created_at('注册时间');
        $grid->disableCreateButton();   // 不显示 '新建' 按钮
        $grid->disableActions();        // 不显示 '编辑' 按钮

        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete(); // 禁用批量删除
            });
        });

        return $grid;
    }


    // protected function detail($id)
    // {
    //     $show = new Show(User::findOrFail($id));
    //     $show->field('id', __('Id'));
    //     $show->field('name', __('Name'));
    //     $show->field('email', __('Email'));
    //     $show->field('email_verified_at', __('Email verified at'));
    //     $show->field('password', __('Password'));
    //     $show->field('remember_token', __('Remember token'));
    //     $show->field('created_at', __('Created at'));
    //     $show->field('updated_at', __('Updated at'));
    //     return $show;
    // }

    // protected function form()
    // {
    //     $form = new Form(new User);
    //     $form->text('name', __('Name'));
    //     $form->email('email', __('Email'));
    //     $form->datetime('email_verified_at', __('Email verified at'))->default(date('Y-m-d H:i:s'));
    //     $form->password('password', __('Password'));
    //     $form->text('remember_token', __('Remember token'));
    //     return $form;
    // }
}
